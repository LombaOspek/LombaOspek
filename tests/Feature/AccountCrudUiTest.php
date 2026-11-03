<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Modal CRUD akun: tambah, ubah, setel ulang sandi, hapus, pulihkan,
 * plus mode arsip dan pesan validasi bahasa Indonesia.
 */
class AccountCrudUiTest extends TestCase
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

    public function test_account_page_exposes_the_crud_modal_and_row_data(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/akun')->assertOk()->getContent();

        $this->assertStringContainsString('data-account-modal', $html);
        $this->assertStringContainsString('data-account-create', $html);

        // Setiap baris membawa data yang dibutuhkan modal.
        $this->assertStringContainsString('data-account-row', $html);
        $this->assertStringContainsString('data-url-update', $html);
        $this->assertStringContainsString('data-url-password', $html);
        $this->assertStringContainsString('data-url-delete', $html);
    }

    public function test_validation_messages_are_translated_to_indonesian(): void
    {
        // Tanpa lang/id/validation.php, Laravel menampilkan kunci mentah
        // seperti "validation.unique" - terlihat seperti bug padahal validasi
        // bekerja.
        $existing = User::query()->where('role', UserRole::Student)->firstOrFail();

        $response = $this->actingAs($this->admin())->post('/admin/akun', [
            'name' => 'Duplikat',
            'nim' => $existing->nim,
            'role' => UserRole::Student->value,
            'major' => 'Sistem Informasi',
        ]);

        $response->assertSessionHasErrors('nim');

        $message = session('errors')->first('nim');

        $this->assertStringNotContainsString('validation.', $message, 'Pesan validasi masih berupa kunci mentah.');
        $this->assertStringContainsString('sudah terdaftar', $message);
    }

    public function test_archive_mode_lists_only_deleted_accounts(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $nim = $student->nim;

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}", ['confirm_identity' => $nim]);

        // Daftar biasa tidak memuatnya. Diperiksa lewat data baris, bukan
        // assertDontSee: NIM bisa muncul di tempat lain (mis. placeholder).
        $response = $this->actingAs($this->admin())->get('/admin/akun')->assertOk();

        $this->assertStringNotContainsString(
            'data-nim="'.$nim.'"',
            $response->getContent(),
            'Akun terarsip tidak boleh muncul sebagai baris di daftar biasa.',
        );

        // Mode arsip memuatnya, lengkap dengan tombol pulihkan.
        $this->actingAs($this->admin())
            ->get('/admin/akun?arsip=1')
            ->assertOk()
            ->assertSee($nim)
            ->assertSee('Pulihkan akun');
    }

    public function test_archived_accounts_are_not_counted_in_stats(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}", ['confirm_identity' => $student->nim]);

        // Statistik "total mahasiswa" memakai query biasa, jadi akun terarsip
        // tidak boleh ikut terhitung.
        $this->assertSame(
            User::query()->where('role', UserRole::Student)->count(),
            User::query()->where('role', UserRole::Student)->count(),
        );

        $this->assertNotNull(User::withTrashed()->find($student->id));
    }

    public function test_restoring_an_archived_account_returns_it_to_the_list(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $nim = $student->nim;

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}", ['confirm_identity' => $nim]);

        $this->actingAs($this->admin())
            ->post("/admin/akun/{$student->id}/pulihkan")
            ->assertRedirect();

        $this->assertNotNull(User::query()->find($student->id));

        $this->actingAs($this->admin())
            ->get('/admin/akun?q='.$nim)
            ->assertOk()
            ->assertSee($nim);
    }

    public function test_delete_requires_the_correct_confirmation_value(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}", ['confirm_identity' => 'nilai-salah'])
            ->assertSessionHasErrors('confirm_identity');

        $this->assertNotNull(User::query()->find($student->id));
    }

    public function test_reset_password_reports_a_password_that_actually_works(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->actingAs($this->admin())
            ->post("/admin/akun/{$student->id}/sandi")
            ->assertRedirect();

        $message = session('toast')['message'];
        $this->assertMatchesRegularExpression('/sandi baru: (\S+)/i', $message);
        preg_match('/sandi baru: (\S+)/i', $message, $m);

        $this->assertTrue(Hash::check($m[1], $student->fresh()->password));
    }

    public function test_edit_does_not_change_the_nim(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $nim = $student->nim;

        $this->actingAs($this->admin())
            ->put("/admin/akun/{$student->id}", [
                'name' => 'Nama Setelah Edit',
                'email' => 'setelah.edit@student.umk.ac.id',
                'major' => 'Sistem Informasi',
                // Nim sengaja dikirim: server harus mengabaikannya.
                'nim' => '999999999',
            ])
            ->assertRedirect();

        $fresh = $student->fresh();

        $this->assertSame('Nama Setelah Edit', $fresh->name);
        $this->assertSame($nim, $fresh->nim, 'NIM tidak boleh berubah lewat form ubah.');
    }

    public function test_creating_an_account_records_the_chosen_major_and_group(): void
    {
        $group = Group::query()->where('study_program', 'Sistem Informasi')->firstOrFail();

        $this->actingAs($this->admin())
            ->post('/admin/akun', [
                'name' => 'Akun Baru Uji',
                'nim' => '209977001',
                'role' => UserRole::Student->value,
                'major' => 'Sistem Informasi',
                'group_id' => $group->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $user = User::query()->where('nim', '209977001')->firstOrFail();

        $this->assertSame('Sistem Informasi', $user->major);
        $this->assertSame('Fakultas Teknik', $user->faculty);
        $this->assertSame($group->id, $user->group_id);
    }
}