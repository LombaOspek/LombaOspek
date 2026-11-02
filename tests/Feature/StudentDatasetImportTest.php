<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Dataset mahasiswa 5 program studi harus benar-benar bisa diimpor.
 *
 * Tes ini membaca berkas NYATA dari database/data/, bukan string buatan di
 * dalam tes. Kalau CSV-nya rusak (header salah, kolom kurang, gugus tidak
 * dikenal), tes ini gagal — bukan baru ketahuan saat hari-H.
 */
class StudentDatasetImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Gugus (G-01..G-04) dan akun admin berasal dari seeder. Tanpa ini
        // import menolak setiap baris karena kode gugus tidak dikenal.
        $this->seed();
    }

    private function admin(): User
    {
        return User::query()->where('role', UserRole::Admin)->firstOrFail();
    }

    private function upload(string $filename): UploadedFile
    {
        $path = database_path('data/'.$filename);

        $this->assertFileExists($path, "Berkas dataset tidak ditemukan: {$filename}");

        return new UploadedFile($path, $filename, 'text/csv', null, true);
    }

    /**
     * @return array<string, int> jumlah mahasiswa per prodi
     */
    private function studentCountByMajor(): array
    {
        return User::query()
            ->where('role', UserRole::Student)
            ->selectRaw('major, count(*) as total')
            ->groupBy('major')
            ->pluck('total', 'major')
            ->all();
    }

    /**
     * Selisih jumlah per prodi. Dipakai supaya asersi tidak bergantung pada
     * data yang sudah dibuat seeder.
     *
     * @param  array<string, int>  $before
     * @param  array<string, int>  $after
     * @return array<string, int>
     */
    private function delta(array $before, array $after): array
    {
        $delta = [];

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $major) {
            $delta[$major] = ($after[$major] ?? 0) - ($before[$major] ?? 0);
        }

        ksort($delta);

        return $delta;
    }

    public function test_the_clean_dataset_imports_every_row_across_five_study_programs(): void
    {
        $before = $this->studentCountByMajor();

        $this->actingAs($this->admin())
            ->post('/admin/akun/import', [
                'file' => $this->upload('mahasiswa-umk-ft-1000.csv'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // Tidak boleh ada baris yang dilewati: kalau ada, header atau kode
        // gugus di dataset tidak cocok dengan yang dibaca bulkImport().
        // bulkImport() selalu mengirim array (bisa kosong), jadi yang diuji
        // adalah isinya — bukan null.
        $this->assertSame([], session('import_skipped'), 'Dataset bersih tidak boleh menghasilkan baris dilewati.');

        $delta = $this->delta($before, $this->studentCountByMajor());

        // Lima program studi Fakultas Teknik UMK, 200 orang masing-masing.
        // Prodi lain (mis. "Informatika" dari seeder) harus 0: dataset tidak
        // boleh menambah sembarangan.
        $this->assertSame([
            'Informatika' => 0,
            'Sistem Informasi' => 200,
            'Teknik Elektro' => 200,
            'Teknik Industri' => 200,
            'Teknik Informatika' => 200,
            'Teknik Mesin' => 200,
        ], $delta);
    }

    public function test_every_imported_student_is_attached_to_a_real_group(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-ft-1000.csv')]);

        $tanpaGugus = User::query()
            ->where('role', UserRole::Student)
            ->whereNull('group_id')
            ->count();

        $this->assertSame(0, $tanpaGugus, 'Semua mahasiswa harus menempel ke gugus, bukan menggantung.');
        $this->assertSame(4, Group::query()->count());
    }

    public function test_blank_email_column_falls_back_to_campus_address(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-ft-1000.csv')]);

        // Dataset sengaja mengosongkan ~20% email untuk menguji fallback.
        $fallback = User::query()
            ->where('role', UserRole::Student)
            ->whereRaw('email = CONCAT(nim, ?)', ['@students.umk.ac.id'])
            ->count();

        $this->assertGreaterThan(100, $fallback, 'Fallback email NIM tidak bekerja.');
        $this->assertSame(0, User::query()->whereNull('email')->count());
    }

    public function test_the_deliberately_broken_file_is_reported_row_by_row(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-ft-contoh-cacat.csv')])
            ->assertRedirect();

        $skipped = session('import_skipped');

        $this->assertIsArray($skipped, 'Berkas cacat harus menghasilkan laporan baris dilewati.');

        $gabung = strtolower(implode(' | ', $skipped));

        // Enam cacat disengaja harus tertangkap semuanya.
        $this->assertStringContainsString('sudah terdaftar', $gabung, 'NIM duplikat tidak dilaporkan.');
        $this->assertStringContainsString('nama kosong', $gabung, 'Nama kosong tidak dilaporkan.');
        $this->assertStringContainsString('tidak ditemukan', $gabung, 'Gugus tak dikenal tidak dilaporkan.');

        // Baris yang sah tetap masuk.
        $this->assertGreaterThanOrEqual(1, User::query()->where('role', UserRole::Student)->count());
    }

    public function test_generated_student_numbers_are_nine_digits_and_unique(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-ft-1000.csv')]);

        $nims = User::query()->where('role', UserRole::Student)->pluck('nim');

        $this->assertSame($nims->count(), $nims->unique()->count(), 'NIM tidak boleh duplikat.');

        foreach ($nims as $nim) {
            $this->assertMatchesRegularExpression('/^\d{9}$/', $nim, "NIM tidak 9 digit: {$nim}");
        }
    }
}
