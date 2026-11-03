<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman admin: dropdown custom, paginasi Indonesia, dan penyaringan LO.
 */
class AdminUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::query()->where('role', UserRole::Admin)->firstOrFail();
    }

    public function test_account_page_renders_custom_dropdowns_instead_of_native_selects(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/akun')->assertOk()->getContent();

        // Komponen dropdown custom terpasang.
        $this->assertStringContainsString('data-dropdown', $html);
        $this->assertStringContainsString('data-dd-trigger', $html);
        $this->assertStringContainsString('role="listbox"', $html);

        // Prodi bergrup per fakultas.
        $this->assertStringContainsString('Fakultas Teknik', $html);
        $this->assertStringContainsString('Teknik Industri', $html);
    }

    public function test_dropdown_labels_are_not_overwritten_by_option_loops(): void
    {
        /*
         * Regresi: loop @foreach di komponen memakai variabel $label, yang
         * menimpa prop $label milik komponen. Akibatnya label field tampil
         * sebagai nama opsi terakhir ("Psikologi" bukan "Program Studi").
         */
        $html = $this->actingAs($this->admin())->get('/admin/akun')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<label for="dd-study_program"[^>]*>\s*Program Studi/',
            $html,
            'Label dropdown prodi harus "Program Studi", bukan nama opsi terakhir.',
        );

        $this->assertMatchesRegularExpression(
            '/<label for="dd-mentor_id"[^>]*>\s*LO Utama/',
            $html,
            'Label dropdown LO harus "LO Utama".',
        );

        // Nama opsi tidak boleh muncul sebagai label field.
        $this->assertDoesNotMatchRegularExpression(
            '/<label for="dd-study_program"[^>]*>\s*Psikologi/',
            $html,
        );
    }

    public function test_pagination_is_in_indonesian(): void
    {
        // Seeder kecil; buat cukup baris agar paginasi muncul.
        $group = Group::query()->first();

        for ($i = 0; $i < 15; $i++) {
            User::create([
                'nim' => '2099500'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'name' => 'Uji Paginasi '.$i,
                'email' => "uji{$i}@example.test",
                'password' => 'password',
                'role' => UserRole::Student,
                'major' => 'Sistem Informasi',
                'faculty' => 'Fakultas Teknik',
                'group_id' => $group?->id,
            ]);
        }

        $html = $this->actingAs($this->admin())->get('/admin/akun')->assertOk()->getContent();

        $this->assertStringContainsString('Menampilkan', $html);
        $this->assertStringContainsString('akun', $html);
        $this->assertStringContainsString('aria-label="Navigasi halaman"', $html);

        // Tidak boleh ada sisa teks Inggris bawaan Laravel.
        $this->assertStringNotContainsString('Showing', $html);
        $this->assertStringNotContainsString('Previous', $html);
    }

    public function test_liaison_officers_are_grouped_per_major_for_the_dropdown(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/akun')->assertOk()->getContent();

        // Peta JSON dipakai JS untuk menyaring LO berdasarkan prodi.
        $this->assertStringContainsString('data-officer-map', $html);

        preg_match('/<script type="application\/json" data-officer-map>(.*?)<\/script>/s', $html, $m);
        $this->assertNotEmpty($m, 'Peta LO per prodi harus ada di halaman.');

        $map = json_decode($m[1], true);
        $this->assertIsArray($map);
        $this->assertArrayHasKey('Sistem Informasi', $map);

        // LO Sistem Informasi harus muncul di prodi itu.
        $siNames = implode(' ', $map['Sistem Informasi'] ?? []);
        $this->assertStringContainsString('Kak', $siNames);
    }

    public function test_a_group_can_be_created_for_a_major_that_already_has_groups(): void
    {
        /*
         * Regresi: aturan "satu LO = satu gugus" pernah membuat form gugus
         * MUSTAHIL dipakai, karena semua LO di seeder sudah membimbing gugus
         * sehingga dropdown-nya kosong. Aturan yang benar hanya soal jurusan.
         */
        $existing = Group::query()->whereNotNull('mentor_id')->firstOrFail();
        $lo = User::query()->findOrFail($existing->mentor_id);

        $this->actingAs($this->admin())
            ->post('/admin/akun/gugus', [
                'name' => 'Kelompok 99 — Uji Sejurusan',
                'study_program' => $existing->study_program,
                'mentor_id' => $lo->id,
                'capacity' => 40,
                'badge_color' => 'sky',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('groups', [
            'name' => 'Kelompok 99 — Uji Sejurusan',
            'mentor_id' => $lo->id,
        ]);
    }

    public function test_group_creation_still_rejects_a_major_mismatch(): void
    {
        $lo = User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();
        $lo->forceFill(['major' => 'Akuntansi'])->save();

        $this->actingAs($this->admin())
            ->post('/admin/akun/gugus', [
                'name' => 'Kelompok Lintas Jurusan',
                'study_program' => 'Sistem Informasi',
                'mentor_id' => $lo->id,
                'capacity' => 40,
                'badge_color' => 'sky',
            ])
            ->assertSessionHasErrors('mentor_id');
    }
}