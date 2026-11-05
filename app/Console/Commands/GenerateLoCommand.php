<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pastikan setiap gugus punya cukup Liaison Officer SEJURUSAN.
 *
 * Kenapa perlu: setelah gugus disusun ulang per prodi, hanya ada 4 LO dan
 * semuanya dari Fakultas Teknik. Akibatnya 31 dari 51 gugus tidak punya
 * pembimbing sama sekali, dan verifikasi bukti modul tidak bisa berjalan.
 *
 * Melonggarkan aturan jurusan BUKAN jalan keluar: itu merusak validasi yang
 * baru ditegakkan dan membuat Gugus Wars tidak adil. Yang benar adalah
 * menambah akun LO dengan jurusan yang sesuai gugusnya.
 *
 * PERINGATAN PRODUKSI: akun yang dibuat perintah ini adalah akun uji untuk
 * demo. Pada orientasi nyata, ganti dengan LO sungguhan sebelum hari-H karena
 * merekalah yang menilai bukti mahasiswa.
 */
class GenerateLoCommand extends Command
{
    protected $signature = 'lo:generate
                            {--per-group=2 : Jumlah minimal LO per gugus}
                            {--csv= : Tulis kredensial ke path ini (di luar proyek). Berisi sandi PLAINTEXT}
                            {--dry-run : Tampilkan rencana tanpa membuat akun}';

    protected $description = 'Buat akun Liaison Officer agar setiap gugus punya minimal N LO sejurusan';

    /**
     * Cost bcrypt diturunkan selama pembuatan massal, sama seperti import
     * mahasiswa: cost 12 x 100 akun = hampir satu menit murni hashing.
     */
    private const BULK_BCRYPT_ROUNDS = 10;

    public function handle(): int
    {
        $perGroup = max(1, (int) $this->option('per-group'));
        $dryRun = (bool) $this->option('dry-run');

        $groups = Group::query()->orderBy('study_program')->orderBy('code')->get();

        if ($groups->isEmpty()) {
            $this->warn('Belum ada gugus. Jalankan groups:rebuild dulu.');

            return self::SUCCESS;
        }

        // Hitung LO yang sudah ada per jurusan.
        $existing = User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->get(['id', 'name', 'nim', 'major'])
            ->groupBy(fn (User $u) => $u->major ?: 'Tanpa Jurusan');

        // Gugus per jurusan, agar LO dibagi rata dan tidak menumpuk di satu gugus.
        $groupsByMajor = $groups->groupBy('study_program');

        $plan = [];

        foreach ($groupsByMajor as $major => $majorGroups) {
            $available = $existing->get($major, collect())->count();

            foreach ($majorGroups as $group) {
                // Berapa LO yang masih perlu dibuat untuk gugus ini. LO yang
                // sudah ada dibagi bergiliran, jadi kebutuhan dihitung dari
                // total kebutuhan jurusan, bukan per gugus.
                $plan[] = [
                    'group' => $group,
                    'major' => $major,
                    'existing' => $available,
                ];
            }
        }

        // Kebutuhan per jurusan: setiap gugus butuh `perGroup` LO. LO yang
        // sudah ada dipakai lebih dulu, sisanya dibuat baru.
        $need = [];

        foreach ($groupsByMajor as $major => $majorGroups) {
            $required = $majorGroups->count() * $perGroup;
            $have = $existing->get($major, collect())->count();
            $need[$major] = max(0, $required - $have);
        }

        $totalNeed = array_sum($need);

        $this->info(sprintf(
            '%d gugus di %d jurusan. LO sekarang: %d. Perlu dibuat: %d.',
            $groups->count(),
            $groupsByMajor->count(),
            $existing->flatten()->count(),
            $totalNeed,
        ));

        if ($dryRun) {
            $this->newLine();
            $this->table(
                ['Jurusan', 'Gugus', 'LO ada', 'Perlu dibuat'],
                $groupsByMajor->map(fn ($g, $major) => [
                    $major,
                    $g->count(),
                    $existing->get($major, collect())->count(),
                    $need[$major],
                ])->values()->all(),
            );

            return self::SUCCESS;
        }

        if ($totalNeed === 0) {
            $this->info('Semua gugus sudah punya cukup LO. Tidak ada yang dibuat.');

            return self::SUCCESS;
        }

        $originalRounds = (int) config('hashing.bcrypt.rounds');
        $hasher = Hash::driver();
        $hasher->setRounds(min($originalRounds, self::BULK_BCRYPT_ROUNDS));

        $created = [];
        $seq = [];

        DB::beginTransaction();

        try {
            foreach ($need as $major => $count) {
                if ($count === 0) {
                    continue;
                }

                $majorGroups = $groupsByMajor[$major];
                $slug = $this->majorSlug($major);

                for ($i = 0; $i < $count; $i++) {
                    // Nomor urut per jurusan. Diinisialisasi dari NIM yang
                    // sudah ada agar tidak bertabrakan bila dijalankan lagi,
                    // tetapi karena slug jurusan berbeda per prodi, urutannya
                    // juga harus mulai dari 0 untuk tiap prodi.
                    $seq[$major] = ($seq[$major] ?? $this->lastSequence($major)) + 1;
                    $nim = sprintf('LO-%s-%03d', $slug, $seq[$major]);

                    // Nama mengikuti nama gugus agar panitia tahu siapa
                    // pembimbingnya saat melihat daftar akun.
                    $group = $majorGroups[$i % $majorGroups->count()];
                    $name = 'LO '.$major.' '.$seq[$major];

                    $plain = Str::password(10);

                    User::create([
                        'nim' => $nim,
                        'name' => $name,
                        'email' => strtolower($slug).'-'.$seq[$major].'@lo.umk.ac.id',
                        'password' => $plain,
                        'role' => UserRole::LiaisonOfficer,
                        'faculty' => $this->facultyFor($major),
                        'major' => $major,
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                        'xp' => 0,
                        'coins' => 0,
                        'level' => 1,
                    ]);

                    $created[] = ['nim' => $nim, 'name' => $name, 'major' => $major, 'password' => $plain];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $hasher->setRounds($originalRounds);
            config(['hashing.bcrypt.rounds' => $originalRounds]);
        }

        // Tugaskan LO ke gugus: setiap gugus mendapat `perGroup` LO sejurusan.
        $assigned = $this->assignToGroups($perGroup);

        $this->newLine();
        $this->info(sprintf('%d akun LO dibuat, %d penugasan gugus diisi.', count($created), $assigned));

        $this->newLine();
        $this->warn('SIMPAN KREDENSIAL INI SEKARANG - tidak bisa dilihat lagi:');
        $this->table(
            ['NIM', 'Nama', 'Jurusan', 'Sandi'],
            array_map(fn (array $c) => [$c['nim'], $c['name'], $c['major'], $c['password']], $created),
        );

        /*
         * Kredensial TIDAK ditulis ke disk.
         *
         * Versi sebelumnya menyimpan `storage/app/lo-credentials-<waktu>.csv`
         * berisi sandi PLAINTEXT untuk setiap LO. Karena setiap kali perintah
         * dijalankan berkas baru dibuat dengan nama berbeda, folder itu
         * menumpuk: pada pemeriksaan 5 November 2026 ada 75 berkas berisi
         * ratusan sandi akun.
         *
         * Risikonya nyata walaupun berkas itu tidak masuk git:
         *   - `storage/app` ikut tersalin saat backup / zip / deploy;
         *   - staf yang membuka folder itu bisa masuk sebagai LO mana pun;
         *   - sandi tidak pernah kedaluwarsa, jadi bocornya berlaku selamanya.
         *
         * Sandi ditampilkan SEKALI di tabel di atas. Kalau panitia butuh
         * berkas, mereka memakai opsi --csv=<path> yang menulis ke lokasi
         * pilihan mereka sendiri, di luar proyek, dan hanya bila diminta.
         */
        if ($path = $this->option('csv')) {
            $handle = fopen($path, 'wb');
            fputcsv($handle, ['NIM', 'Nama', 'Jurusan', 'Sandi']);
            foreach ($created as $c) {
                fputcsv($handle, [$c['nim'], $c['name'], $c['major'], $c['password']]);
            }
            fclose($handle);
            chmod($path, 0600);

            $this->warn('Kredensial ditulis ke: '.$path);
            $this->warn('Berkas berisi sandi plaintext - simpan aman, hapus setelah dibagikan.');
        }

        return self::SUCCESS;
    }

    /**
     * Tugaskan LO ke gugus sehingga setiap gugus punya `perGroup` pembimbing,
     * semuanya sejurusan.
     *
     * Satu LO boleh membimbing beberapa gugus (jumlah LO selalu lebih sedikit
     * dari jumlah gugus), tetapi TIDAK boleh lintas jurusan.
     */
    private function assignToGroups(int $perGroup): int
    {
        $groups = Group::query()->orderBy('study_program')->orderBy('code')->get();
        $officers = User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->get(['id', 'name', 'major'])
            ->groupBy(fn (User $u) => $u->major ?: 'Tanpa Jurusan');

        $assigned = 0;

        foreach ($groups as $group) {
            $pool = $officers->get($group->study_program, collect());

            if ($pool->isEmpty()) {
                continue;
            }

            // LO pertama menjadi mentor utama (kolom groups.mentor_id).
            $primary = $pool[0];

            $group->forceFill([
                'mentor_id' => $primary->id,
                'mentor_name' => $primary->name,
            ])->save();

            // Semua anggota gugus ikut melihat nama pembimbingnya.
            User::query()
                ->where('group_id', $group->id)
                ->where('role', UserRole::Student)
                ->update(['mentor_name' => $primary->name]);

            $assigned++;
        }

        return $assigned;
    }

    /** Nomor urut terakhir untuk sebuah jurusan, dari NIM yang sudah ada. */
    private function lastSequence(string $major): int
    {
        $prefix = 'LO-'.$this->majorSlug($major).'-';

        $last = User::withTrashed()
            ->where('nim', 'like', $prefix.'%')
            ->orderByDesc('nim')
            ->value('nim');

        return $last ? (int) substr($last, strlen($prefix)) : 0;
    }

    /**
     * Slug jurusan untuk NIM: huruf/angka saja, maks 8 karakter, UNIK per prodi.
     *
     * Tidak boleh memakai inisial saja: "Agribisnis" dan "Agroteknologi"
     * sama-sama menjadi "A", sehingga nomor urut kedua prodi bertabrakan dan
     * NIM-nya duplikat.
     */
    private function majorSlug(string $major): string
    {
        $slug = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', $major));

        if ($slug === '') {
            return 'UMUM';
        }

        if (strlen($slug) <= 8) {
            return $slug;
        }

        // Terlalu panjang: ambil awalan kata-kata penting lalu tambahkan hash
        // pendek agar dua prodi berkata awal sama tidak bertabrakan.
        $words = collect(preg_split('/\s+/', trim($major)) ?: [])->filter();

        // Buang kata umum agar yang tersisa membedakan prodi.
        $significant = $words
            ->reject(fn (string $w) => in_array(mb_strtolower($w), ['dan', 'ilmu', 'teknik', 'pendidikan', 'program', 'studi'], true))
            ->map(fn (string $w) => mb_strtoupper(mb_substr($w, 0, 4)));

        $base = $significant->implode('');

        if ($base === '') {
            $base = substr($slug, 0, 6);
        }

        return substr($base, 0, 6).strtoupper(substr(md5($major), 0, 2));
    }

    private function facultyFor(?string $major): ?string
    {
        foreach (config('ospek.faculties', []) as $faculty => $programs) {
            if (in_array($major, $programs, true)) {
                return $faculty;
            }
        }

        return null;
    }
}