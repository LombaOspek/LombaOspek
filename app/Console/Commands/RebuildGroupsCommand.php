<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Susun ulang gugus agar setiap gugus berisi SATU program studi.
 *
 * Kenapa perlu: data awal hanya punya 5 gugus untuk 17 program studi, sehingga
 * 2.241 dari 3.070 mahasiswa berada di gugus jurusan lain (Kelompok 04 berisi
 * 15 prodi sekaligus). Aturan "LO hanya membimbing gugus sejurusan" jadi tidak
 * bisa ditegakkan, dan Gugus Wars membandingkan kelompok yang tidak setara.
 *
 * Perintah ini mengelompokkan mahasiswa per prodi, memecah prodi besar menjadi
 * beberapa gugus agar kapasitas wajar, lalu menugaskan LO yang sejurusan.
 *
 * Aman diulang: mahasiswa yang sudah berada di gugus sejurusan dengan ukuran
 * wajar tidak dipindah, dan gugus lama yang menjadi kosong dihapus.
 */
class RebuildGroupsCommand extends Command
{
    protected $signature = 'groups:rebuild
                            {--size=70 : Jumlah anggota maksimal per gugus}
                            {--dry-run : Tampilkan rencana tanpa mengubah data}';

    protected $description = 'Susun ulang gugus agar satu gugus = satu program studi';

    /** Warna lencana dipakai bergiliran supaya gugus mudah dibedakan. */
    private const COLORS = ['amber', 'teal', 'sky', 'vermilion'];

    public function handle(): int
    {
        $size = max(10, (int) $this->option('size'));
        $dryRun = (bool) $this->option('dry-run');

        $students = User::query()
            ->where('role', UserRole::Student)
            ->get(['id', 'nim', 'name', 'major']);

        if ($students->isEmpty()) {
            $this->warn('Tidak ada mahasiswa. Impor data dulu.');

            return self::SUCCESS;
        }

        $byMajor = $students->groupBy(fn (User $u) => $u->major ?: 'Tanpa Jurusan');

        // LO dikelompokkan per jurusan agar penugasan tidak lintas prodi.
        $officers = User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->get(['id', 'name', 'major'])
            ->groupBy(fn (User $u) => $u->major ?: 'Tanpa Jurusan');

        $plan = [];

        foreach ($byMajor as $major => $members) {
            $chunks = $members->sortBy('nim')->chunk($size)->values();

            foreach ($chunks as $index => $chunk) {
                $plan[] = [
                    'major' => $major,
                    'index' => $index + 1,
                    'members' => $chunk,
                ];
            }
        }

        $this->info(sprintf(
            '%d mahasiswa, %d program studi -> %d gugus (maks %d anggota).',
            $students->count(),
            $byMajor->count(),
            count($plan),
            $size,
        ));

        if ($dryRun) {
            $this->newLine();
            $this->table(
                ['Prodi', 'Gugus', 'Anggota', 'Rencana LO'],
                collect($plan)->map(fn (array $p) => [
                    $p['major'],
                    $this->groupName($p['major'], $p['index'], $byMajor[$p['major']]->count() > $size ? true : false),
                    $p['members']->count(),
                    $officers->get($p['major'])?->count() ?? 0,
                ])->all(),
            );

            return self::SUCCESS;
        }

        $created = 0;
        $moved = 0;
        $withoutOfficer = 0;

        DB::transaction(function () use ($plan, $officers, $byMajor, $size, &$created, &$moved, &$withoutOfficer): void {
            foreach ($plan as $entry) {
                $major = $entry['major'];
                $multi = $byMajor[$major]->count() > $size;

                $group = Group::query()->firstOrCreate(
                    ['code' => $this->groupCode($major, $entry['index'], $multi)],
                    [
                        'name' => $this->groupName($major, $entry['index'], $multi),
                        'study_program' => $major,
                        'capacity' => $size + 10,
                        'badge_color' => self::COLORS[($entry['index'] - 1) % count(self::COLORS)],
                        'invitation_code' => Group::generateInvitationCode($this->groupName($major, $entry['index'], $multi)),
                    ],
                );

                if ($group->wasRecentlyCreated) {
                    $created++;
                }

                // LO sejurusan dibagi bergiliran; LO boleh membimbing beberapa
                // gugus selama satu jurusan.
                $majorOfficers = $officers->get($major);

                if ($majorOfficers && $majorOfficers->isNotEmpty()) {
                    $officer = $majorOfficers[($entry['index'] - 1) % $majorOfficers->count()];

                    $group->forceFill([
                        'mentor_id' => $officer->id,
                        'mentor_name' => $officer->name,
                    ])->save();
                } else {
                    $withoutOfficer++;

                    $group->forceFill(['mentor_id' => null, 'mentor_name' => null])->save();
                }

                // Pindahkan anggota. Nama gugus pada profil ikut disegarkan
                // karena dipakai di banyak tampilan.
                $ids = $entry['members']->pluck('id')->all();

                $moved += User::query()
                    ->whereIn('id', $ids)
                    ->update([
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                        'mentor_name' => $group->mentor_name,
                    ]);
            }

            /*
             * Hapus gugus yang tidak dipakai rencana ini (gugus lama dari
             * seeding awal). Menyisakan gugus kosong membuat Gugus Wars
             * menampilkan bar yang tidak bermakna, dan gugus sisa dengan nama
             * lama membingungkan panitia.
             *
             * Dihapus berdasarkan kode rencana, BUKAN whereDoesntHave: relasi
             * members tidak melihat baris yang soft-deleted, sehingga gugus
             * yang masih memuat akun terarsip bisa lolos dan tertinggal.
             */
            $plannedCodes = collect($plan)
                ->map(fn (array $p) => $this->groupCode($p['major'], $p['index'], $byMajor[$p['major']]->count() > $size))
                ->all();

            $stale = Group::query()
                ->whereNotIn('code', $plannedCodes)
                ->whereDoesntHave('members')
                ->get();

            foreach ($stale as $group) {
                $group->delete();
            }
        });

        $this->newLine();
        $this->info(sprintf(
            'Selesai: %d gugus dibuat, %d mahasiswa dipindahkan, %d gugus tanpa LO.',
            $created,
            $moved,
            $withoutOfficer,
        ));

        if ($withoutOfficer > 0) {
            $this->warn(sprintf(
                'Ada %d gugus tanpa Liaison Officer karena tidak ada LO dari prodi tersebut. '
                .'Tambahkan LO lewat halaman admin (Tambah Akun) agar verifikasi bukti bisa berjalan.',
                $withoutOfficer,
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Nama gugus: nomor urut hanya ditampilkan bila prodi punya lebih dari
     * satu gugus, supaya "Psikologi" tidak menjadi "Psikologi 1".
     */
    private function groupName(string $major, int $index, bool $multi): string
    {
        return $multi ? $major.' '.$index : $major;
    }

    /**
     * Kode gugus harus unik per PRODI, bukan sekadar per indeks.
     *
     * Slug 6 huruf membuat "Pendidikan Bahasa Inggris" dan "Pendidikan Bahasa
     * dan Sastra Indonesia" sama-sama menjadi "PENDID" - dua prodi berbeda
     * memakai kode yang sama, dan firstOrCreate menaruh anggotanya ke gugus
     * prodi lain.
     *
     * Karena itu hash pendek dari nama prodi ikut disertakan.
     */
    private function groupCode(string $major, int $index, bool $multi): string
    {
        $slug = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', $major));

        // Ambil huruf awal tiap kata (maks 3) lalu tambah hash 3 huruf.
        $initials = collect(preg_split('/\s+/', trim($major)) ?: [])
            ->filter()
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->take(3)
            ->implode('');

        $prefix = $initials !== '' ? $initials : mb_substr($slug !== '' ? $slug : 'GUGUS', 0, 3);
        $suffix = strtoupper(substr(md5($major), 0, 3));

        $base = $prefix.'-'.$suffix;

        return $multi ? $base.'-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT) : $base;
    }
}
