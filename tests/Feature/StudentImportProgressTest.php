<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Import CSV bertahap: progres nyata, batas waktu, dan biaya hashing.
 *
 * Bug yang dikunci di sini: batch berbasis JUMLAH baris (200) dikali bcrypt
 * cost 12 (~460ms) = 92 detik, sehingga request mati sebelum selesai dan
 * progres tidak pernah kembali ke klien. Batch kini dibatasi WAKTU.
 */
class StudentImportProgressTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $tempFiles = [];

    /** @var array<int, string> */
    private array $filesBefore = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Rekam isi folder sebelum tes; apa pun yang muncul sesudahnya adalah
        // milik tes ini (termasuk berkas <uuid>.csv yang dibuat /init).
        $this->filesBefore = glob(storage_path('app/imports').'/*.csv') ?: [];
    }

    private function admin(): User
    {
        return User::query()->where('role', UserRole::Admin)->firstOrFail();
    }

    /**
     * Berkas CSV sementara yang dibuat tes ini, untuk dibersihkan di tearDown.
     *
     * Tes ini menulis ke `storage/app/imports` SUNGGUHAN (bukan Storage::fake),
     * jadi tanpa pembersihan setiap eksekusi suite meninggalkan NIM, nama, dan
     * email mahasiswa uji di folder data aplikasi. Terbukti: 6 berkas
     * tertinggal saat tes kebocoran kredensial dijalankan.
     *
     * @var array<int, string>
     */
    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        foreach (glob(storage_path('app/imports').'/*.csv') ?: [] as $file) {
            if (! in_array($file, $this->filesBefore, true) && is_file($file)) {
                @unlink($file);
            }
        }

        $this->tempFiles = [];
        $this->filesBefore = [];

        parent::tearDown();
    }

    private function csv(array $rows, string $name = 'import.csv'): string
    {
        $dir = storage_path('app/imports');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Nama unik agar antar-tes tidak saling menimpa.
        $name = uniqid('test-', true).'-'.$name;
        $path = $dir.DIRECTORY_SEPARATOR.$name;
        $this->tempFiles[] = $path;
        $handle = fopen($path, 'wb');
        fputcsv($handle, ['NIM', 'Nama Penuh', 'Jurusan', 'Email Kampus', 'Kode Gugus']);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $path;
    }

    /** @return array<int, array<int, string>> */
    private function manyRows(int $count, string $prefix = '20990'): array
    {
        $group = Group::query()->first();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                $prefix.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'Mahasiswa Uji '.$i,
                'Sistem Informasi',
                '',
                $group?->invitation_code ?? '',
            ];
        }

        return $rows;
    }

    public function test_count_rows_ignores_the_header(): void
    {
        $path = $this->csv($this->manyRows(7));
        $service = new StudentImportService;

        $this->assertSame(7, $service->countRows($path));
    }

    public function test_a_batch_respects_its_time_budget_instead_of_row_count(): void
    {
        $path = $this->csv($this->manyRows(240));
        $service = new StudentImportService;

        $start = microtime(true);
        $batch = $service->processBatch($path, 0, true);
        $elapsed = microtime(true) - $start;

        // Anggaran 2,5 detik + kelonggaran untuk hashing baris terakhir.
        $this->assertLessThan(
            12.0,
            $elapsed,
            'Satu batch tidak boleh memakan waktu puluhan detik: request akan mati dan progres hilang.',
        );

        $this->assertGreaterThan(0, $batch['processed'], 'Batch harus memproses sesuatu.');
        $this->assertLessThanOrEqual(StudentImportService::MAX_ROWS_PER_BATCH, $batch['processed']);
        $this->assertFalse($batch['done'], 'Batch yang berhenti karena waktu BUKAN berarti berkas habis.');
    }

    public function test_repeated_batches_eventually_finish_the_file_exactly_once(): void
    {
        $total = 60;
        $path = $this->csv($this->manyRows($total));
        $service = new StudentImportService;

        $offset = 0;
        $created = 0;
        $rounds = 0;

        do {
            $batch = $service->processBatch($path, $offset, true);
            $offset += $batch['processed'];
            $created += $batch['created'];
            $rounds++;

            $this->assertLessThan(50, $rounds, 'Batch tidak boleh berputar tanpa akhir.');
        } while (! $batch['done'] && $batch['processed'] > 0);

        $this->assertSame($total, $created, 'Setiap baris harus dibuat tepat sekali.');
        $this->assertSame($total, User::query()->where('nim', 'like', '20990%')->count());
    }

    public function test_bulk_import_hashes_passwords_with_the_cheaper_bulk_cost(): void
    {
        // Cost 12 x 1000 akun = ~8 menit murni hashing. Cost 10 tetap kuat
        // untuk sandi sementara, dan Hash::check tetap bekerja karena cost
        // tersimpan di dalam hash itu sendiri.
        $path = $this->csv($this->manyRows(3, '20991'));
        $service = new StudentImportService;

        $batch = $service->processBatch($path, 0, true);

        $this->assertSame(3, $batch['created']);
        $this->assertNotEmpty($batch['credentials']);

        $user = User::query()->where('nim', '209910001')->firstOrFail();
        $info = password_get_info($user->password);

        // Import memakai cost paling mahal 10 (produksi: 10, bukan 12), tetapi
        // menghormati nilai lebih murah di test agar suite tidak butuh menit.
        $this->assertLessThanOrEqual(
            10,
            $info['options']['cost'],
            'Import massal tidak boleh memakai cost 12: 1000 akun = ~8 menit hashing.',
        );
        $this->assertSame(
            min((int) config('hashing.bcrypt.rounds'), 10),
            $info['options']['cost'],
        );

        // Dan sandi yang dilaporkan ke admin benar-benar berlaku.
        $reported = collect($batch['credentials'])->firstWhere('nim', '209910001');
        $this->assertTrue(Hash::check($reported['password'], $user->password));
    }

    public function test_init_endpoint_returns_the_real_row_total_for_progress(): void
    {
        $path = $this->csv($this->manyRows(25, '20992'));

        $response = $this->actingAs($this->admin())
            ->postJson('/admin/akun/import/init', [
                'file' => new \Illuminate\Http\UploadedFile($path, 'data.csv', 'text/csv', null, true),
                'auto_generate_password' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('total', 25);

        $this->assertNotEmpty($response->json('import_id'));
    }

    public function test_batch_endpoint_reports_increasing_progress_and_finishes(): void
    {
        $path = $this->csv($this->manyRows(30, '20993'));

        $init = $this->actingAs($this->admin())
            ->postJson('/admin/akun/import/init', [
                'file' => new \Illuminate\Http\UploadedFile($path, 'data.csv', 'text/csv', null, true),
                'auto_generate_password' => true,
            ])
            ->assertOk();

        $importId = $init->json('import_id');
        $processed = 0;
        $done = false;
        $guard = 0;

        while (! $done && $guard < 40) {
            $guard++;

            $res = $this->actingAs($this->admin())
                ->postJson("/admin/akun/import/{$importId}/batch", [])
                ->assertOk()
                ->assertJsonPath('ok', true);

            $this->assertGreaterThanOrEqual($processed, $res->json('processed'), 'Progres tidak boleh mundur.');
            $processed = $res->json('processed');
            $done = (bool) $res->json('done');
        }

        $this->assertTrue($done, 'Import harus selesai.');
        $this->assertSame(30, $processed);
        $this->assertSame(30, User::query()->where('nim', 'like', '20993%')->count());
    }

    public function test_batch_endpoint_rejects_an_unknown_import_id(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/akun/import/tidak-ada/batch', [])
            ->assertStatus(404)
            ->assertJsonPath('ok', false);
    }

    public function test_import_endpoints_reject_non_admin_users(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $path = $this->csv($this->manyRows(2, '20994'));

        $this->actingAs($student)
            ->postJson('/admin/akun/import/init', [
                'file' => new \Illuminate\Http\UploadedFile($path, 'data.csv', 'text/csv', null, true),
            ])
            ->assertForbidden();
    }
}
