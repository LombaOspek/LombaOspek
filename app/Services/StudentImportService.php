<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Import mahasiswa massal, dirancang agar bisa dijalankan BERTAHAP.
 *
 * Kenapa dipisah dari controller: import 1210 baris butuh 2-4 detik di lokal
 * tetapi bisa 30+ detik di hosting gratis. Tanpa queue worker (tidak tersedia
 * di InfinityFree/VPS kecil), satu-satunya cara menampilkan progres NYATA
 * adalah memecah pekerjaan menjadi batch yang dipanggil klien berulang.
 *
 * Yang dijaga:
 *  - satu baris = satu keputusan; baris cacat dilewati, bukan menggagalkan batch
 *  - NIM duplikat dilewati (termasuk duplikat di dalam berkas yang sama)
 *  - kode gugus tak dikenal dilaporkan, mahasiswa tetap dibuat tanpa gugus
 *  - setiap batch punya transaksi sendiri, jadi batch gagal tidak merusak
 *    batch yang sudah selesai
 */
class StudentImportService
{
    /**
     * Batas waktu satu batch, dalam detik.
     *
     * Jumlah baris BUKAN ukuran yang tepat: bcrypt cost 12 butuh ~460ms per
     * sandi, jadi 200 baris = 92 detik dan request mati sebelum selesai
     * (progres tidak pernah kembali ke klien). Batas waktu membuat batch
     * selalu selesai, berapa pun lambatnya server.
     */
    public const TIME_BUDGET_SECONDS = 2.5;

    /**
     * Batas atas baris per batch, untuk berkas tanpa pembuatan sandi
     * (hashed password dipakai ulang) yang bisa diproses jauh lebih cepat.
     */
    public const MAX_ROWS_PER_BATCH = 200;

    /**
     * Biaya bcrypt untuk sandi hasil import massal.
     *
     * cost 12 (~460ms) x 1000 akun = ~8 menit murni hashing: tidak layak untuk
     * import. cost 10 (~115ms) tetap jauh di atas batas yang membuat brute
     * force praktis (bcrypt 10 ≈ 115ms/tebakan) dan sandi ini hanya sementara.
     * Hash menyimpan cost-nya sendiri, jadi Hash::check tetap bekerja benar.
     */
    private const BULK_BCRYPT_ROUNDS = 10;

    private const EXPECTED_COLUMNS = 5;

    /**
     * Hitung total baris data (di luar header) tanpa memuat seluruh berkas.
     */
    public function countRows(string $absolutePath): int
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return 0;
        }

        $count = 0;
        $first = true;

        while (fgetcsv($handle) !== false) {
            if ($first) {
                $first = false;

                continue;
            }

            $count++;
        }

        fclose($handle);

        return $count;
    }

    /**
     * Proses satu potongan baris.
     *
     * @return array{processed: int, created: int, skipped: array<int, string>, credentials: array<int, array{nim: string, name: string, password: string}>, done: bool}
     */
    public function processBatch(string $absolutePath, int $offset, bool $autoPassword): array
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            throw new \RuntimeException('Berkas import tidak dapat dibaca.');
        }

        // Lompati header + baris sebelum offset.
        fgetcsv($handle);

        for ($i = 0; $i < $offset; $i++) {
            if (fgetcsv($handle) === false) {
                break;
            }
        }

        $processed = 0;
        $reachedEnd = false;
        $created = 0;
        $skipped = [];
        $credentials = [];
        $rowNumber = $offset + 1;

        // Cost bcrypt diturunkan HANYA selama import massal.
        //
        // Cast 'hashed' di model menolak hash yang cost-nya berbeda dari
        // config('hashing.bcrypt.rounds') — jadi menurunkan config adalah cara
        // yang benar, bukan melewati cast. Cost 12 x 1000 akun = ~8 menit
        // murni hashing; cost 10 = ~100 detik, dan Hash::check tetap bekerja
        // karena cost tersimpan di dalam hash.
        $originalRounds = (int) config('hashing.bcrypt.rounds');

        // Import massal TIDAK PERNAH lebih mahal dari cost 10, tetapi tetap
        // menghormati nilai yang lebih murah (lingkungan test memakai cost 4
        // agar suite cepat). Memaksa 10 selalu membuat import 1000 baris pada
        // test butuh ~100 detik hashing tanpa menguji apa pun soal keamanan.
        $bulkRounds = min($originalRounds, self::BULK_BCRYPT_ROUNDS);

        // Hasher adalah singleton yang rounds-nya sudah di-resolve saat dibuat,
        // jadi mengubah config saja tidak berpengaruh. setRounds() yang benar.
        $hasher = Hash::driver();
        $hasher->setRounds($bulkRounds);

        DB::beginTransaction();

        try {
            $deadline = microtime(true) + self::TIME_BUDGET_SECONDS;

            while ($processed < self::MAX_ROWS_PER_BATCH) {
                // Batas waktu, bukan jumlah baris: bcrypt lambat, dan request
                // yang mati di tengah jalan lebih buruk daripada batch kecil.
                if (microtime(true) >= $deadline && $processed > 0) {
                    break;
                }

                $line = fgetcsv($handle);

                if ($line === false) {
                    // EOF adalah satu-satunya penanda "selesai": batch bisa
                    // berhenti lebih awal karena anggaran waktu, dan itu BUKAN
                    // berarti berkas habis.
                    $reachedEnd = true;

                    break;
                }

                $processed++;
                $rowNumber++;

                // Baris kosong tidak dihitung sebagai kegagalan.
                if ($line === [null] || count(array_filter($line, fn ($v) => $v !== null && $v !== '')) === 0) {
                    continue;
                }

                $result = $this->importRow($line, $rowNumber, $autoPassword);

                if ($result['ok']) {
                    $created++;
                    $credentials[] = $result['credential'];
                } else {
                    // Baris gagal total: tidak dibuat, alasannya dilaporkan.
                    $skipped[] = $result['reason'];

                    continue;
                }

                // Baris dibuat TETAPI bermasalah (mis. gugus tak dikenal):
                // mahasiswanya tetap masuk, tetapi panitia harus tahu agar CSV
                // bisa diperbaiki. Tanpa ini, masalahnya hilang diam-diam.
                if (isset($result['reason'])) {
                    $skipped[] = $result['reason'];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            throw $e;
        } finally {
            // Selalu dipulihkan, termasuk saat batch gagal: kalau tidak,
            // sandi pengguna lain di request yang sama ikut jadi cost 10.
            $hasher->setRounds($originalRounds);
            config(['hashing.bcrypt.rounds' => $originalRounds]);
        }

        fclose($handle);

        return [
            'processed' => $processed,
            'created' => $created,
            'skipped' => $skipped,
            'credentials' => $credentials,
            'done' => $reachedEnd,
        ];
    }

    /**
     * @param  array<int, string|null>  $line
     * @return array{ok: bool, reason?: string, credential?: array{nim: string, name: string, password: string}}
     */
    private function importRow(array $line, int $rowNumber, bool $autoPassword): array
    {
        // Urutan kolom sesuai template: NIM, Nama, Jurusan, Email, Kode Gugus
        $line = array_pad($line, self::EXPECTED_COLUMNS, null);

        [$nim, $name, $major, $email, $groupCode] = $line;

        $nim = trim((string) $nim);
        $name = trim((string) $name);

        if ($nim === '' || $name === '') {
            return ['ok' => false, 'reason' => "Baris {$rowNumber}: NIM atau nama kosong"];
        }

        if (User::withTrashed()->where('nim', $nim)->exists()) {
            return ['ok' => false, 'reason' => "Baris {$rowNumber}: NIM {$nim} sudah terdaftar"];
        }

        $group = filled($groupCode)
            ? Group::query()
                ->where(fn ($g) => $g->where('invitation_code', trim((string) $groupCode))
                    ->orWhere('code', trim((string) $groupCode))
                    ->orWhere('name', trim((string) $groupCode)))
                ->first()
            : null;

        // Kode gugus tak dikenal tidak boleh membuat mahasiswa masuk tanpa
        // gugus secara diam-diam: laporkan agar CSV bisa diperbaiki.
        $reason = null;

        if (filled($groupCode) && $group === null) {
            $reason = "Baris {$rowNumber}: gugus \"".trim((string) $groupCode).'" tidak ditemukan';
        }

        $majorName = $this->normalizeMajor($major);
        $plainPassword = $autoPassword ? Str::password(10) : 'password';

        User::create([
            'nim' => $nim,
            'name' => $name,
            'email' => filled($email) ? trim((string) $email) : $nim.'@students.umk.ac.id',
            // Plaintext: cast 'hashed' di model yang meng-hash, memakai
            // rounds yang disetel processBatch() untuk seluruh batch.
            'password' => $plainPassword,
            'role' => UserRole::Student,
            'faculty' => $this->facultyFor($majorName),
            'major' => $majorName,
            'group_id' => $group?->id,
            'group_name' => $group?->name,
            'mentor_name' => $group?->mentor_name,
            'xp' => 0,
            'coins' => 0,
            'level' => 1,
        ]);

        if ($reason !== null) {
            // Mahasiswa dibuat tetapi gugusnya tidak dikenal — tetap laporkan.
            return [
                'ok' => true,
                'credential' => ['nim' => $nim, 'name' => $name, 'password' => $plainPassword],
                'reason' => $reason,
            ];
        }

        return [
            'ok' => true,
            'credential' => ['nim' => $nim, 'name' => $name, 'password' => $plainPassword],
        ];
    }

    /**
     * Ejaan jurusan dinormalkan ke nama resmi dari config.
     */
    public function normalizeMajor(?string $major): string
    {
        $input = trim((string) $major);

        if ($input === '') {
            return 'Sistem Informasi';
        }

        foreach (config('ospek.faculties', []) as $programs) {
            foreach ($programs as $program) {
                if (strcasecmp($program, $input) === 0) {
                    return $program;
                }
            }
        }

        return $input;
    }

    /**
     * Fakultas pemilik jurusan. Null bila jurusan tidak terdaftar, supaya
     * tidak salah klaim.
     */
    public function facultyFor(?string $major): ?string
    {
        $normalized = $this->normalizeMajor($major);

        foreach (config('ospek.faculties', []) as $faculty => $programs) {
            if (in_array($normalized, $programs, true)) {
                return $faculty;
            }
        }

        return null;
    }
}
