<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Manajemen akun tingkat lanjut: soft delete, restore, tambah manual,
 * edit, reset sandi, dan aturan LO satu jurusan.
 */
class AccountLifecycleTest extends TestCase
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

    private function student(): User
    {
        return User::query()->where('role', UserRole::Student)->firstOrFail();
    }

    /* ---------------- Soft delete ---------------- */

    public function test_deleting_a_student_keeps_their_xp_history(): void
    {
        $student = $this->student();

        XpTransaction::create([
            'user_id' => $student->id,
            'amount' => 120,
            'balance_after' => 120,
            'source_type' => 'quest',
            'source_id' => 1,
            'description' => 'Riwayat sebelum dihapus',
            'idempotency_key' => 'softdelete:test:1',
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}", ['confirm_identity' => $student->nim])
            ->assertRedirect();

        // Akun hilang dari query biasa...
        $this->assertNull(User::query()->find($student->id));

        // ...tetapi riwayat XP TETAP ADA. Inilah alasan soft delete: hard delete
        // akan menghapusnya permanen lewat cascadeOnDelete.
        $this->assertSame(1, XpTransaction::where('user_id', $student->id)->count());
        $this->assertNotNull(User::withTrashed()->find($student->id));
    }

    public function test_delete_requires_identity_confirmation(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}", ['confirm_identity' => 'salah'])
            ->assertSessionHasErrors('confirm_identity');

        $this->assertNotNull(User::query()->find($student->id), 'Akun tidak boleh terhapus tanpa konfirmasi benar.');
    }

    public function test_deleted_account_cannot_log_in(): void
    {
        $student = $this->student();
        $nim = $student->nim;

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}", ['confirm_identity' => $nim]);

        auth()->logout();

        $this->post('/login', ['identity' => $nim, 'password' => 'password'])
            ->assertSessionHasErrors('identity');
    }

    public function test_a_deleted_student_can_be_restored(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}", ['confirm_identity' => $student->nim]);

        $this->actingAs($this->admin())
            ->post("/admin/akun/{$student->id}/pulihkan")
            ->assertRedirect();

        $this->assertNotNull(User::query()->find($student->id), 'Akun harus kembali aktif.');
    }

    /* ---------------- Tambah manual ---------------- */

    public function test_admin_can_create_a_liaison_officer_with_a_major(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun', [
                'name' => 'Kak Uji',
                'nim' => 'LO-2023-999',
                'role' => UserRole::LiaisonOfficer->value,
                'major' => 'Akuntansi',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $lo = User::query()->where('nim', 'LO-2023-999')->firstOrFail();

        $this->assertSame(UserRole::LiaisonOfficer, $lo->role);
        $this->assertSame('Akuntansi', $lo->major);
        // Fakultas diturunkan dari config, bukan hardcode.
        $this->assertSame('Fakultas Ekonomi dan Bisnis', $lo->faculty);
        $this->assertSame(0, $lo->coins);
    }

    public function test_manual_create_rejects_duplicate_nim(): void
    {
        $existing = $this->student();

        $this->actingAs($this->admin())
            ->post('/admin/akun', [
                'name' => 'Duplikat',
                'nim' => $existing->nim,
                'role' => UserRole::Student->value,
                'major' => 'Akuntansi',
            ])
            ->assertSessionHasErrors('nim');
    }

    public function test_manual_create_rejects_group_from_another_major(): void
    {
        $group = Group::query()->where('study_program', 'Sistem Informasi')->firstOrFail();

        $this->actingAs($this->admin())
            ->post('/admin/akun', [
                'name' => 'Salah Jurusan',
                'nim' => '209900123',
                'role' => UserRole::Student->value,
                'major' => 'Akuntansi',
                'group_id' => $group->id,
            ])
            ->assertSessionHasErrors('group_id');
    }

    /* ---------------- Edit & reset sandi ---------------- */

    public function test_admin_can_edit_a_student(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())
            ->put("/admin/akun/{$student->id}", [
                'name' => 'Nama Baru',
                'email' => 'baru@student.umk.ac.id',
                'major' => 'Psikologi',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fresh = $student->fresh();

        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertSame('Psikologi', $fresh->major);
        $this->assertSame('Fakultas Psikologi', $fresh->faculty);
    }

    public function test_admin_can_reset_a_password_and_it_actually_works(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())
            ->post("/admin/akun/{$student->id}/sandi")
            ->assertRedirect()
            ->assertSessionHas('toast');

        // Sandi baru ditampilkan di toast; ambil dari sesi untuk diuji.
        $message = session('toast')['message'];

        $this->assertMatchesRegularExpression('/sandi baru: (\S+)/i', $message);
        preg_match('/sandi baru: (\S+)/i', $message, $m);

        $this->assertTrue(
            Hash::check($m[1], $student->fresh()->password),
            'Sandi yang ditampilkan harus benar-benar berlaku.',
        );
    }

    /* ---------------- LO satu jurusan ---------------- */

    public function test_group_creation_rejects_a_liaison_officer_from_another_major(): void
    {
        $lo = User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();
        $lo->forceFill(['major' => 'Akuntansi'])->save();

        $this->actingAs($this->admin())
            ->post('/admin/akun/gugus', [
                'name' => 'Gugus Uji Lintas Jurusan',
                'study_program' => 'Sistem Informasi',
                'mentor_id' => $lo->id,
                'capacity' => 30,
                'badge_color' => 'amber',
            ])
            ->assertSessionHasErrors('mentor_id');

        $this->assertDatabaseMissing('groups', ['name' => 'Gugus Uji Lintas Jurusan']);
    }

    public function test_a_liaison_officer_may_mentor_several_groups_in_the_same_major(): void
    {
        /*
         * Jumlah LO selalu jauh lebih sedikit daripada jumlah gugus (4 LO untuk
         * puluhan gugus), jadi membatasi satu LO ke satu gugus akan memblokir
         * pembuatan gugus sama sekali. Yang dibatasi adalah LINTAS JURUSAN.
         */
        $existing = Group::query()->whereNotNull('mentor_id')->firstOrFail();
        $lo = User::query()->where('role', UserRole::LiaisonOfficer)->findOrFail($existing->mentor_id);

        $this->actingAs($this->admin())
            ->post('/admin/akun/gugus', [
                'name' => 'Gugus Kedua Sejurusan',
                'study_program' => $existing->study_program,
                'mentor_id' => $lo->id,
                'capacity' => 30,
                'badge_color' => 'amber',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            Group::query()->where('mentor_id', $lo->id)->count(),
            'LO harus boleh membimbing lebih dari satu gugus pada jurusan yang sama.',
        );
    }
}
