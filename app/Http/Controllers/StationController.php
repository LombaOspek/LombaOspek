<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Station penyerahan hadiah untuk Liaison Officer.
 *
 * Kenapa halaman terpisah, bukan `/admin/bursa`:
 *   Rute admin berada di grup `role:admin`, sehingga LO ditolak 403 oleh
 *   middleware SEBELUM mencapai controller. Padahal `AdminRewardController::
 *   fulfill()` sudah mengizinkan LO - izin itu tidak pernah tercapai.
 *   Menu sidebar LO juga menautkan ke `admin.rewards`, jadi LO melihat menu
 *   yang pasti menghasilkan 403 ketika diklik.
 *
 * Halaman ini hanya memuat pekerjaan LO: daftar klaim mahasiswa gugusnya, dan
 * penyerahan berdasarkan kode klaim. Admin tetap memegang harga/stok di
 * `/admin/bursa` - satu harga untuk seluruh kampus.
 */
class StationController extends Controller
{
    /**
     * Daftar klaim pada gugus LO, dikelompokkan menurut status.
     */
    public function index(Request $request): View
    {
        $officer = $request->user();
        $this->ensureOfficer($officer);

        // Filter status dari query string; default menampilkan yang belum diambil.
        $status = $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'fulfilled', 'cancelled', 'all'], true)) {
            $status = 'pending';
        }

        /*
         * Normalisasi kode sebelum mencari.
         *
         * Alfabet kode klaim (RewardService::CLAIM_ALPHABET) sengaja tidak
         * memuat huruf I, O, angka 0 dan 1 - untuk menghindari salah baca.
         * Tetapi pengguna TETAP mengetik "0" ketika melihat "O", karena di
         * layar banyak font membuat keduanya nyaris sama.
         *
         * Kasus nyata 10 November 2026: kode di layar `OQ-R97N-J4D8`, diketik
         * `0Q-R97N-J4D8`, hasil pencarian kosong, dan dilaporkan sebagai
         * "masih tidak bisa di-redeem" padahal klaimnya ada.
         *
         * Karena 0 dan 1 tidak pernah muncul dalam kode asli, menggantinya
         * dengan O dan I aman: tidak ada kode sah yang berubah arti.
         */
        $cari = trim((string) $request->query('q', ''));

        /*
         * `$cari` yang diteruskan ke view adalah versi yang SUDAH dinormalisasi,
         * supaya kotak pencarian menampilkan kode yang benar setelah pengguna
         * mengetik `0Q-...`. Tanpa ini, layar tetap memperlihatkan angka nol
         * padahal hasilnya sudah ketemu — membingungkan saat menjelaskan ke
         * petugas station.
         */
        $cari = self::normalizeClaimCode($cari);

        $memberIds = $this->memberIds($officer);

        $query = RewardRedemption::query()
            ->with(['reward:id,name,code,image_path', 'user:id,name,nim,group_id'])
            ->whereIn('user_id', $memberIds)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($cari !== '', function ($q) use ($cari) {
                // Pencarian kode klaim dipakai di meja station: panitia membaca
                // kode dari layar mahasiswa lalu mengetiknya di sini.
                $q->where(function ($inner) use ($cari) {
                    $inner->where('claim_code', 'like', '%'.$cari.'%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$cari.'%')
                            ->orWhere('nim', 'like', '%'.$cari.'%'));
                });
            })
            /*
             * Urutan status memakai CASE, bukan FIELD().
             *
             * FIELD() adalah fungsi MySQL saja; tes berjalan di SQLite dan
             * langsung gagal "no such function: FIELD". CASE portabel di
             * keduanya, jadi urutan yang sama berlaku di produksi (MariaDB)
             * maupun di suite tes.
             */
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'fulfilled' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at');

        // Dihitung SEBELUM paginasi, supaya kartu ringkasan menunjukkan total
        // gugus, bukan total halaman yang sedang dibuka.
        $counts = [
            'pending' => RewardRedemption::query()->whereIn('user_id', $memberIds)->where('status', 'pending')->count(),
            'fulfilled' => RewardRedemption::query()->whereIn('user_id', $memberIds)->where('status', 'fulfilled')->count(),
            'cancelled' => RewardRedemption::query()->whereIn('user_id', $memberIds)->where('status', 'cancelled')->count(),
        ];

        /*
         * Kalau pencarian kosong padahal kartu "Menunggu" menunjukkan ada
         * klaim, penyebab tersering BUKAN kode salah - melainkan klaim itu
         * milik gugus lain. Tanpa penjelasan, layar hanya berkata "tidak ada
         * yang cocok" dan petugas mengira sistemnya rusak.
         *
         * Kasus nyata: DEMO-LO memegang gugus 45 (Sistem Informasi 1),
         * sedangkan klaim yang dicari milik gugus 46 (Sistem Informasi 2).
         */
        $elsewhere = null;

        if ($cari !== '' && ! $query->exists()) {
            $found = RewardRedemption::query()
                ->with('user:id,name,nim,group_id')
                ->where('claim_code', $cari)
                ->first();

            if ($found !== null) {
                $elsewhere = [
                    'code' => $found->claim_code,
                    'status' => $found->status,
                    'name' => $found->user?->name,
                    'nim' => $found->user?->nim,
                    'group' => \App\Models\Group::query()->find($found->user?->group_id)?->name,
                ];
            }
        }

        return view('pages.lo.station', [
            'claims' => $query->paginate(15)->withQueryString(),
            'counts' => $counts,
            'status' => $status,
            'cari' => $cari,
            'group' => $officer->group,
            'memberCount' => $memberIds->count(),
            'elsewhere' => $elsewhere,
        ]);
    }

    /**
     * Serahkan satu klaim. Dipisah dari AdminRewardController::fulfill karena
     * pemeriksaan kepemilikan gugus di sini lebih ketat: LO hanya boleh
     * menyerahkan klaim mahasiswa yang berada di gugusnya SEKARANG.
     */
    public function fulfill(Request $request, RewardRedemption $redemption): RedirectResponse
    {
        $officer = $request->user();
        $this->ensureOfficer($officer);

        $target = $redemption->user;

        abort_unless(
            $target !== null
                && $officer->group_id !== null
                && $target->group_id === $officer->group_id,
            403,
            'Klaim ini milik mahasiswa gugus lain.',
        );

        if ($redemption->status === 'fulfilled') {
            return back()->withErrors([
                'redemption' => 'Klaim '.$redemption->claim_code.' sudah diserahkan. Jangan berikan barang dua kali.',
            ]);
        }

        if ($redemption->status === 'cancelled') {
            return back()->withErrors([
                'redemption' => 'Klaim '.$redemption->claim_code.' sudah dibatalkan, tidak ada barang yang diserahkan.',
            ]);
        }

        $redemption->forceFill([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
        ])->save();

        Log::info('Hadiah diserahkan via station LO.', [
            'oleh' => $officer->getKey(),
            'officer_nim' => $officer->nim,
            'redemption_id' => $redemption->getKey(),
            'claim_code' => $redemption->claim_code,
        ]);

        return back()->with('toast', [
            'title' => 'Hadiah diserahkan',
            'message' => 'Klaim '.$redemption->claim_code.' sudah ditandai diambil.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Cari klaim berdasarkan kode - dipakai kotak pencarian di meja station.
     * Tujuan: panitia bisa langsung mengetik kode dari QR/teks tanpa menggulir.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $officer = $request->user();
        $this->ensureOfficer($officer);

        $code = strtoupper(trim((string) $request->query('code', '')));

        if ($code === '') {
            return back();
        }

        $redemption = RewardRedemption::query()->where('claim_code', $code)->first();

        if ($redemption === null) {
            return back()->withErrors(['code' => 'Kode klaim '.$code.' tidak ditemukan.']);
        }

        return redirect()->route('lo.station', ['q' => $code]);
    }

    /**
     * Rapikan kode klaim hasil ketikan manual.
     *
     * Kode memakai alfabet tanpa I, O, 0, dan 1 supaya tidak ambigu saat
     * dibaca. Tetapi justru karena tampilannya mirip, orang mengetik angka
     * untuk huruf: "OQ-R97N-J4D8" diketik "0Q-R97N-J4D8" dan pencarian gagal.
     * Karena 0/1 tidak pernah ada di kode sah, menukarnya aman.
     *
     * Nama dan NIM tidak boleh diubah, jadi hanya bagian yang berbentuk kode
     * klaim yang dinormalisasi.
     */
    private static function normalizeClaimCode(string $value): string
    {
        if ($value === '') {
            return '';
        }

        // Bentuk kode klaim: OQ-XXXX-XXXX (setelah diganti, bisa juga oq-...)
        $upper = strtoupper($value);

        if (! preg_match('/^[0O][Q][-\s]?[A-Z0-9]{4}[-\s]?[A-Z0-9]{4}$/', $upper)) {
            return $value;
        }

        $compact = preg_replace('/[^A-Z0-9]/', '', $upper);

        // Huruf pertama selalu O (bukan nol), dan 0/1 di dalam kode adalah
        // salah baca untuk O/I.
        $compact = str_replace(['0', '1'], ['O', 'I'], $compact);

        return 'OQ-'.substr($compact, 2, 4).'-'.substr($compact, 6, 4);
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private function memberIds(User $officer)
    {
        if ($officer->group_id === null) {
            return collect();
        }

        return User::query()->where('group_id', $officer->group_id)->pluck('id');
    }

    private function ensureOfficer(?User $user): void
    {
        abort_unless(
            $user !== null && $user->role === UserRole::LiaisonOfficer,
            403,
            'Halaman ini hanya untuk Liaison Officer.',
        );
    }
}