<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Manajemen akun & gugus oleh superadmin: import massal, pembuatan gugus,
 * dan batas otorisasi.
 */
class AccountManagementTest extends TestCase
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

    private function officer(): User
    {
        return User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();
    }

    private function csvFile(string $body): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv').'.csv';
        file_put_contents($path, $body);

        return new UploadedFile($path, 'mahasiswa.csv', 'text/csv', null, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Import CSV
    |--------------------------------------------------------------------------
    */

    public function test_bulk_import_creates_accounts_and_assigns_group_by_code(): void
    {
        $group = Group::query()->firstOrFail();

        $csv = "NIM,Nama,Jurusan,Email,Kode Gugus\n"
            ."209900901,Siti Aminah,Sistem Informasi,siti@student.umk.ac.id,{$group->code}\n"
            ."209900902,Budi Hartono,Informatika,budi@student.umk.ac.id,{$group->invitation_code}\n";

        $this->actingAs($this->admin())
            ->post('/admin/akun/import', [
                'file' => $this->csvFile($csv),
                'auto_generate_password' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // Kode gugus (G-0x) harus menghasilkan keanggotaan, bukan diam-diam null.
        $siti = User::query()->where('nim', '209900901')->firstOrFail();
        $this->assertSame($group->id, $siti->group_id);
        $this->assertSame($group->name, $siti->group_name);

        // Kode undangan juga harus dikenali.
        $budi = User::query()->where('nim', '209900902')->firstOrFail();
        $this->assertSame($group->id, $budi->group_id);

        $this->assertSame(UserRole::Student, $siti->role);
        $this->assertSame(0, $siti->xp);
    }

    public function test_bulk_import_skips_duplicate_nim_without_failing_the_batch(): void
    {
        $existing = User::query()->where('role', UserRole::Student)->firstOrFail();

        $csv = "NIM,Nama,Jurusan,Email,Kode Gugus\n"
            ."{$existing->nim},Nama Bentrok,Sistem Informasi,bentrok@student.umk.ac.id,\n"
            ."209900903,Orang Baru,Sistem Informasi,baru@student.umk.ac.id,\n";

        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->csvFile($csv)])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // Baris duplikat dilewati, baris lain tetap masuk.
        $this->assertDatabaseHas('users', ['nim' => '209900903']);
        $this->assertSame(1, User::query()->where('nim', $existing->nim)->count());
        $this->assertSame($existing->name, $existing->refresh()->name);

        $skipped = session('import_skipped');
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('sudah terdaftar', $skipped[0]);
    }

    public function test_bulk_import_reports_unknown_group_code(): void
    {
        $csv = "NIM,Nama,Jurusan,Email,Kode Gugus\n"
            ."209900904,Rina Wati,Sistem Informasi,rina@student.umk.ac.id,G-99\n";

        $this->actingAs($this->admin())
            ->post('/admin/akun/import', ['file' => $this->csvFile($csv)])
            ->assertRedirect();

        $skipped = session('import_skipped');
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('tidak ditemukan', $skipped[0]);
    }

    public function test_bulk_import_rejects_non_csv_file(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/akun/import', [
                'file' => UploadedFile::fake()->create('data.pdf', 40, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_students_and_officers_cannot_import(): void
    {
        $csv = "NIM,Nama,Jurusan,Email,Kode Gugus\n209900905,X,Sistem Informasi,x@umk.ac.id,\n";

        $this->actingAs($this->officer())
            ->post('/admin/akun/import', ['file' => $this->csvFile($csv)])
            ->assertForbidden();

        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->actingAs($student)
            ->post('/admin/akun/import', ['file' => $this->csvFile($csv)])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['nim' => '209900905']);
    }

    /*
    |--------------------------------------------------------------------------
    | Gugus
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_a_group_and_officer_is_moved_into_it(): void
    {
        $officer = $this->officer();
        $before = Group::query()->count();

        $this->actingAs($this->admin())
            ->post('/admin/akun/gugus', [
                'name' => 'Kelompok Uji Rajawali',
                'study_program' => 'Sistem Informasi',
                'mentor_id' => $officer->id,
                'capacity' => 60,
                'badge_color' => 'teal',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($before + 1, Group::query()->count());

        $group = Group::query()->where('name', 'Kelompok Uji Rajawali')->firstOrFail();
        $this->assertSame($officer->id, $group->mentor_id);
        $this->assertNotEmpty($group->invitation_code);

        // LO harus ikut masuk agar bisa meninjau bukti gugusnya.
        $this->assertSame($group->id, $officer->refresh()->group_id);
    }

    public function test_group_name_must_be_unique(): void
    {
        $existing = Group::query()->firstOrFail();

        $this->actingAs($this->admin())
            ->post('/admin/akun/gugus', [
                'name' => $existing->name,
                'study_program' => 'Sistem Informasi',
                'mentor_id' => $this->officer()->id,
                'capacity' => 50,
                'badge_color' => 'amber',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_group_requires_an_actual_liaison_officer_as_mentor(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->actingAs($this->admin())
            ->post('/admin/akun/gugus', [
                'name' => 'Kelompok Salah Mentor',
                'study_program' => 'Sistem Informasi',
                'mentor_id' => $student->id,
                'capacity' => 50,
                'badge_color' => 'sky',
            ])
            ->assertSessionHasErrors('mentor_id');

        $this->assertDatabaseMissing('groups', ['name' => 'Kelompok Salah Mentor']);
    }

    /*
    |--------------------------------------------------------------------------
    | Peran & penghapusan akun
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_change_a_user_role(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $group = Group::query()->firstOrFail();

        $this->actingAs($this->admin())
            ->post("/admin/akun/{$student->id}/peran", [
                'role' => UserRole::LiaisonOfficer->value,
                'group_id' => $group->id,
            ])
            ->assertRedirect();

        $student->refresh();
        $this->assertSame(UserRole::LiaisonOfficer, $student->role);
        $this->assertSame($group->id, $student->group_id);
    }

    public function test_admin_cannot_demote_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/admin/akun/{$admin->id}/peran", ['role' => UserRole::Student->value])
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete("/admin/akun/{$admin->id}")
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_account(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->actingAs($this->admin())
            ->delete("/admin/akun/{$student->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_template_download_contains_expected_header(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/akun/template');

        $response->assertOk();
        $this->assertStringContainsString('NIM', $response->streamedContent());
        $this->assertStringContainsString('Kode Gugus', $response->streamedContent());
    }
}