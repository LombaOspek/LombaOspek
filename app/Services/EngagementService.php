<?php

namespace App\Services;

use App\Models\EngagementClaim;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Otak lima fitur engagement.
 *
 * Prinsip yang dipegang di sini:
 *
 * 1. Seluruh hadiah XP WAJIB lewat XpService dengan idempotency key. Klien
 *    tidak pernah menentukan jumlah hadiah - server yang menghitung.
 * 2. Batas "satu klaim per periode" ditegakkan tabel engagement_claims, bukan
 *    localStorage. Klien boleh berbohong soal apa yang sudah diklaim; database
 *    tidak bisa.
 * 3. Tidak ada pencapaian fiktif. Feed FOMO, gelar, dan gugus wars semuanya
 *    dibaca dari XP asli milik mahasiswa lain.
 */
class EngagementService
{
    public function __construct(
        private readonly XpService $xp,
        private readonly CoinService $coins,
    ) {}

    /* ------------------------------------------------------------------
     * 1. Gugus Wars — adu tarik XP antargugus
     * ---------------------------------------------------------------- */

    /**
     * Dua gugus teratas sebagai lawan tarik tambang, plus rasio kekuatan untuk
     * menentukan seberapa jauh tali tertarik.
     *
     * @return array{
     *     available: bool,
     *     left: array{name: string, xp: int, members: int}|null,
     *     right: array{name: string, xp: int, members: int}|null,
     *     ratio: float,
     *     my_side: string|null,
     *     gap: int,
     *     message: string
     * }
     */
    public function gugusWars(User $user): array
    {
        $standings = app(LeaderboardService::class)->groupStandings(8);

        $empty = [
            'available' => false,
            'left' => null,
            'right' => null,
            'ratio' => 0.5,
            'my_side' => null,
            'gap' => 0,
            'message' => 'Gugus Wars aktif setelah cukup banyak mahasiswa menyelesaikan modul.',
        ];

        if ($standings->count() < 2) {
            return $empty;
        }

        // Duduk perkara: pertarungan selalu antara peringkat 1 dan 2 supaya
        // terasa seperti final, tapi gugus user sendiri selalu ditampilkan
        // agar ia punya taruhan di dalamnya.
        $first = $standings->get(0);
        $second = $standings->get(1);

        $mine = $user->group_name;

        $pair = [$first, $second];

        if ($mine !== null && $mine !== '') {
            $self = $standings->first(fn (array $row): bool => $row['group'] === $mine);

            if ($self !== null && $self['rank'] > 2) {
                // Tempatkan gugus user di kiri, lawannya = peringkat 1.
                $pair = [$self, $first];
            } elseif ($self !== null && $self['rank'] === 2) {
                $pair = [$self, $first];
            } elseif ($self !== null && $self['rank'] === 1) {
                $pair = [$self, $second];
            }
        }

        [$left, $right] = $pair;

        $total = max(1, $left['total_xp'] + $right['total_xp']);

        // Rasio 0..1: 0 = kiri kalah total, 1 = kiri menang total.
        $ratio = $left['total_xp'] / $total;

        $gap = abs($left['total_xp'] - $right['total_xp']);

        return [
            'available' => true,
            'left' => [
                'name' => (string) $left['group'],
                'xp' => (int) $left['total_xp'],
                'members' => (int) $left['members'],
            ],
            'right' => [
                'name' => (string) $right['group'],
                'xp' => (int) $right['total_xp'],
                'members' => (int) $right['members'],
            ],
            'ratio' => round($ratio, 4),
            'my_side' => $mine === $left['group'] ? 'left' : ($mine === $right['group'] ? 'right' : null),
            'gap' => $gap,
            'message' => $gap === 0
                ? 'Kedudukan imbang! Satu modul lagi menentukan siapa yang menang.'
                : sprintf(
                    '%s unggul %s XP. Setiap modul yang Anda selesaikan menambah tarikan.',
                    $left['total_xp'] >= $right['total_xp'] ? $left['group'] : $right['group'],
                    number_format($gap, 0, ',', '.'),
                ),
        ];
    }

    /* ------------------------------------------------------------------
     * 2. Kotak Misteri Harian
     * ---------------------------------------------------------------- */

    /**
     * Status kotak hari ini. Hadiah sudah ditentukan (deterministik per
     * user+tanggal) supaya bisa ditampilkan di riwayat setelah diklaim.
     *
     * @return array{key: string, claimed: bool, value: int, claimed_at: string|null}
     */
    public function dailyBox(User $user): array
    {
        $period = Carbon::today()->toDateString();
        $claim = $this->claimFor($user, 'daily_box', $period);

        return [
            'key' => $period,
            'claimed' => $claim !== null,
            'value' => $claim !== null ? $claim->value : $this->plannedRoll($user, $period),
            'claimed_at' => $claim?->created_at?->toIso8601String(),
        ];
    }

    /**
     * Klaim kotak hari ini. Mengembalikan null bila sudah pernah diklaim -
     * pemanggil wajib membedakan "sudah klaim" dari "gagal".
     *
     * @return array{xp: int, balance_after: int|null, already: bool}
     */
    public function claimDailyBox(User $user): array
    {
        $period = Carbon::today()->toDateString();
        $value = $this->plannedRoll($user, $period);

        $claim = $this->recordClaim($user, 'daily_box', $period, $value);

        if ($claim === null) {
            return ['xp' => 0, 'balance_after' => null, 'already' => true];
        }

        $boxKey = "daily_box:{$user->getKey()}:{$period}";

        $balance = $this->xp->award(
            $user,
            $value,
            'daily_box',
            null,
            'Kotak Misteri Harian',
            $boxKey,
        );

        $this->coins->awardForXp($user, $value, 'daily_box', null, 'Koin Kotak Misteri', $boxKey);

        return ['xp' => $value, 'balance_after' => $balance, 'already' => false];
    }

    /**
     * Hadiah yang "seharusnya" keluar hari ini. Deterministik: hash dari id
     * user + tanggal. Konsekuensinya, membuka DevTools dan mengubah angka di
     * klien tidak mengubah hadiah yang benar-benar dibayarkan server.
     */
    private function plannedRoll(User $user, string $period): int
    {
        $config = config('ospek.engagement.daily_box');
        $min = (int) $config['min'];
        $max = (int) $config['max'];
        $step = max(1, (int) $config['step']);

        $steps = (int) floor(($max - $min) / $step);

        $seed = crc32($user->getKey().'|'.$period.'|'.$user->nim);
        $bucket = $steps > 0 ? ($seed % ($steps + 1)) : 0;

        return $min + $bucket * $step;
    }

    /* ------------------------------------------------------------------
     * 3. Misi Rahasia (easter egg maskot)
     * ---------------------------------------------------------------- */

    /**
     * Klaim Misi Rahasia. Server hanya memverifikasi bahwa syarat klik sudah
     * dipenuhi klien; jumlah XP tetap dari config, dan `period = 'once'`
     * membuatnya mustahil diulang.
     *
     * @return array{xp: int, balance_after: int|null, already: bool}
     */
    public function claimSecretMission(User $user, int $clicks): array
    {
        $required = (int) config('ospek.engagement.secret_mission.clicks');

        if ($clicks < $required) {
            return ['xp' => 0, 'balance_after' => null, 'already' => false];
        }

        $reward = (int) config('ospek.engagement.secret_mission.xp');
        $claim = $this->recordClaim($user, 'secret_mission', 'once', $reward);

        if ($claim === null) {
            return ['xp' => 0, 'balance_after' => null, 'already' => true];
        }

        $secretKey = "secret_mission:{$user->getKey()}";

        $balance = $this->xp->award(
            $user,
            $reward,
            'secret_mission',
            null,
            'Misi Rahasia: WIRA membocorkan jalan pintas',
            $secretKey,
        );

        $this->coins->awardForXp($user, $reward, 'secret_mission', null, 'Koin Misi Rahasia', $secretKey);

        return ['xp' => $reward, 'balance_after' => $balance, 'already' => false];
    }

    /* ------------------------------------------------------------------
     * 4. Digital Flexing — gelar + bingkai avatar
     * ---------------------------------------------------------------- */

    /**
     * Tentukan gelar seorang mahasiswa dari XP dan peringkatnya.
     *
     * Urutan config dari atas ke bawah; gelar pertama yang syaratnya terpenuhi
     * dipakai. XP rendah tetap mendapat gelar netral agar tidak ada baris
     * leaderboard yang terasa "kosong".
     *
     * @return array{label: string, frame: string}
     */
    public function titleFor(User $user, ?int $rank = null): array
    {
        $titles = config('ospek.engagement.titles', []);

        foreach ($titles as $title) {
            $minXp = $title['min_xp'] ?? null;
            $rankMax = $title['rank_max'] ?? null;

            $xpOk = $minXp === null || $user->xp >= (int) $minXp;
            $rankOk = $rankMax === null || ($rank !== null && $rank <= (int) $rankMax);

            if ($xpOk && $rankOk) {
                return ['label' => (string) $title['label'], 'frame' => (string) $title['frame']];
            }
        }

        return ['label' => 'Maba Baru', 'frame' => 'plain'];
    }

    /* ------------------------------------------------------------------
     * 5. FOMO Live Feed
     * ---------------------------------------------------------------- */

    /**
     * Aktivitas XP nyata dari mahasiswa lain dalam jendela waktu tertentu.
     *
     * Dibatasi ke sumber yang layak dipamerkan: modul, kuis, lencana, dan
     * kotak harian. Entri rahasia tidak dimasukkan karena membocorkan easter
     * egg ke seluruh kampus.
     *
     * @return Collection<int, array{name: string, action: string, xp: int, ago: string}>
     */
    public function liveFeed(User $user): Collection
    {
        $config = config('ospek.engagement.live_feed');
        $limit = (int) $config['max_items'];
        $since = Carbon::now()->subMinutes((int) $config['window_minutes']);

        $rows = XpTransaction::query()
            ->with('user:id,name,group_name')
            ->where('amount', '>', 0)
            ->where('created_at', '>=', $since)
            ->where('user_id', '!=', $user->getKey())
            ->whereIn('source_type', ['quest', 'quiz', 'achievement', 'daily_box'])
            ->orderByDesc('created_at')
            ->limit($limit * 3)
            ->get();

        // Satu orang cukup muncul sekali: feed yang diisi nama sama terlihat
        // seperti bot dan justru merusak kepercayaan.
        return $rows
            ->unique(fn (XpTransaction $row): int => (int) $row->user_id)
            ->take($limit)
            ->values()
            ->map(function (XpTransaction $row): array {
                $name = $row->user?->name ?? 'Mahasiswa';

                return [
                    'name' => $this->maskName($name),
                    'action' => $this->feedAction($row),
                    'xp' => (int) $row->amount,
                    'ago' => $row->created_at?->diffForHumans(short: true) ?? 'baru saja',
                ];
            });
    }

    /**
     * Nama disamarkan sebagian ("Aldi F." bukan "Aldiansyah Fayruz") agar feed
     * tidak menjadi daftar nama lengkap seluruh mahasiswa.
     */
    private function maskName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = $parts[0] ?? 'Mahasiswa';

        if (count($parts) === 1) {
            return $first;
        }

        $initial = mb_substr($parts[count($parts) - 1], 0, 1);

        return $first.' '.mb_strtoupper($initial).'.';
    }

    private function feedAction(XpTransaction $row): string
    {
        return match ($row->source_type) {
            'quest' => 'menyelesaikan modul',
            'quiz' => 'lulus kuis',
            'achievement' => 'membuka lencana',
            'daily_box' => 'membuka Kotak Misteri',
            default => 'mendapat XP',
        };
    }

    /* ------------------------------------------------------------------
     * Util klaim
     * ---------------------------------------------------------------- */

    private function claimFor(User $user, string $kind, string $period): ?EngagementClaim
    {
        return EngagementClaim::query()
            ->where('user_id', $user->getKey())
            ->where('kind', $kind)
            ->where('period', $period)
            ->first();
    }

    /**
     * Catat klaim sekali jalan. Mengembalikan null bila periode itu sudah
     * diklaim (termasuk saat dua permintaan berbarengan menabrak unique index).
     */
    private function recordClaim(User $user, string $kind, string $period, int $value): ?EngagementClaim
    {
        try {
            return EngagementClaim::create([
                'user_id' => $user->getKey(),
                'kind' => $kind,
                'period' => $period,
                'value' => $value,
            ]);
        } catch (\Illuminate\Database\QueryException $exception) {
            // 23000 = pelanggaran integritas (unique index). Ini jalur normal
            // saat klien mengirim dua permintaan sekaligus.
            Log::info('Klaim engagement ditolak karena periode sudah dipakai.', [
                'user_id' => $user->getKey(),
                'kind' => $kind,
                'period' => $period,
            ]);

            unset($exception);

            return null;
        }
    }
}
