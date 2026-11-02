<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Dataset mahasiswa harus benar-benar bisa diimpor, untuk SELURUH fakultas.
 *
 * OspekQuest dipakai satu universitas (6 fakultas, 16 prodi S1), bukan satu
 * fakultas. Tes ini membaca berkas NYATA dari database/data/, jadi kalau
 * CSV-nya rusak (header salah, gugus tidak dikenal) tes gagal di sini — bukan
 * baru ketahuan saat hari-H.
 *
 * Asersi memakai SELISIH sebelum→sesudah import karena seeder sudah membuat
 * akun demo lebih dulu; total mentah akan mengikat tes pada isi seeder.
 */
class StudentDatasetImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Gugus (G-01..G-04) dan akun admin berasal dari seeder.
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
     * @return array<string, int>
     */
    private function countBy(string $column): array
    {
        return User::query()
            ->where('role', UserRole::Student)
            ->selectRaw("{$column} as k, count(*) as total")
            ->groupBy($column)
            ->pluck('total', 'k')
            ->all();
    }

    /**
     * @param  array<string, int>  $before
     * @param  array<string, int>  $after
     * @return array<string, int>
     */
    private function delta(array $before, array $after): array
    {
        $delta = [];

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            $delta[$key] = ($after[$key] ?? 0) - ($before[$key] ?? 0);
        }

        ksort($delta);

        return $delta;
    }

    public function test_the_university_wide_dataset_covers_all_six_faculties(): void
    {
        $before = $this->countBy('faculty');

        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-1000.csv')])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame([], session('import_skipped'), 'Dataset bersih tidak boleh menghasilkan baris dilewati.');

        $delta = $this->delta($before, $this->countBy('faculty'));

        // Enam fakultas UMK, dengan jumlah tidak rata seperti kampus sungguhan.
        $this->assertSame([
            'Fakultas Ekonomi dan Bisnis' => 220,
            'Fakultas Hukum' => 70,
            'Fakultas Keguruan dan Ilmu Pendidikan' => 385,
            'Fakultas Pertanian' => 115,
            'Fakultas Psikologi' => 65,
            'Fakultas Teknik' => 355,
        ], $delta);

        $this->assertSame(1210, array_sum($delta));
    }

    public function test_every_study_program_gets_the_correct_faculty(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-1000.csv')]);

        foreach (config('ospek.faculties') as $faculty => $programs) {
            foreach ($programs as $program) {
                $salahFakultas = User::query()
                    ->where('role', UserRole::Student)
                    ->where('major', $program)
                    ->where('faculty', '!=', $faculty)
                    ->count();

                $this->assertSame(0, $salahFakultas, "Prodi {$program} seharusnya di {$faculty}.");
            }
        }
    }

    public function test_all_sixteen_study_programs_are_present(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-1000.csv')]);

        $expectedPrograms = collect(config('ospek.faculties'))->flatten()->all();

        $this->assertCount(16, $expectedPrograms, 'Config fakultas harus memuat 16 prodi S1.');

        foreach ($expectedPrograms as $program) {
            $this->assertGreaterThan(
                0,
                User::query()->where('role', UserRole::Student)->where('major', $program)->count(),
                "Prodi {$program} tidak muncul di data hasil import.",
            );
        }
    }

    public function test_every_imported_student_is_attached_to_a_real_group(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-1000.csv')]);

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
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-1000.csv')]);

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
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-contoh-cacat.csv')])
            ->assertRedirect();

        $skipped = session('import_skipped');

        $this->assertIsArray($skipped, 'Berkas cacat harus menghasilkan laporan baris dilewati.');

        $gabung = strtolower(implode(' | ', $skipped));

        $this->assertStringContainsString('sudah terdaftar', $gabung, 'NIM duplikat tidak dilaporkan.');
        $this->assertStringContainsString('nama kosong', $gabung, 'Nama kosong tidak dilaporkan.');
        $this->assertStringContainsString('tidak ditemukan', $gabung, 'Gugus tak dikenal tidak dilaporkan.');

        $this->assertGreaterThanOrEqual(1, User::query()->where('role', UserRole::Student)->count());
    }

    public function test_generated_student_numbers_are_nine_digits_and_unique(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->upload('mahasiswa-umk-1000.csv')]);

        $nims = User::query()->where('role', UserRole::Student)->pluck('nim');

        $this->assertSame($nims->count(), $nims->unique()->count(), 'NIM tidak boleh duplikat.');

        foreach ($nims as $nim) {
            $this->assertMatchesRegularExpression('/^\d{9}$/', $nim, "NIM tidak 9 digit: {$nim}");
        }
    }
}
