<?php

namespace Tests\Feature;

use App\Enums\QuestStatus;
use App\Enums\UserRole;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk ke portal')
            ->assertSee('OspekQuest');
    }

    public function test_guest_is_redirected_from_protected_routes(): void
    {
        foreach (['/', '/dashboard', '/misi', '/pencapaian', '/sijil', '/peringkat', '/profil'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_student_can_login_with_nim(): void
    {
        $this->post('/login', [
            'identity' => '202653033',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/login', [
            'identity' => '202653033',
            'password' => 'salah-total',
        ])->assertSessionHasErrors('identity');

        $this->assertGuest();
    }

    public function test_quick_login_bypass_route_no_longer_exists(): void
    {
        $this->get('/quick-login')->assertNotFound();
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post('/login', ['identity' => '202653033', 'password' => 'salah']);
        }

        $this->post('/login', ['identity' => '202653033', 'password' => 'salah'])
            ->assertStatus(429);
    }

    public function test_logout_ends_the_session(): void
    {
        $student = User::query()->where('nim', '202653033')->firstOrFail();

        $this->actingAs($student)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | Otorisasi berbasis peran
    |--------------------------------------------------------------------------
    */

    public function test_student_cannot_open_officer_portal(): void
    {
        $student = User::query()->where('nim', '202653033')->firstOrFail();

        $this->actingAs($student)->get('/lo/verifikasi')->assertForbidden();
    }

    public function test_student_cannot_open_admin_pages(): void
    {
        $student = User::query()->where('nim', '202653033')->firstOrFail();

        $this->actingAs($student)->get('/admin/akun')->assertForbidden();
        $this->actingAs($student)->get('/admin/misi')->assertForbidden();
    }

    public function test_liaison_officer_cannot_open_admin_pages(): void
    {
        $officer = User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();

        $this->actingAs($officer)->get('/admin/akun')->assertForbidden();
    }

    public function test_liaison_officer_can_open_verification_portal(): void
    {
        $officer = User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();

        $this->actingAs($officer)->get('/lo/verifikasi')->assertOk();
    }

    public function test_admin_can_open_both_portals(): void
    {
        $admin = User::query()->where('role', UserRole::Admin)->firstOrFail();

        $this->actingAs($admin)->get('/admin/akun')->assertOk();
        $this->actingAs($admin)->get('/admin/misi')->assertOk();
        $this->actingAs($admin)->get('/lo/verifikasi')->assertOk();
    }

    public function test_first_quest_is_open_on_a_fresh_account(): void
    {
        $student = User::query()->where('nim', '202653033')->firstOrFail();
        $first = Quest::query()->ordered()->firstOrFail();

        $this->assertSame(
            QuestStatus::Available,
            app(ProgressionService::class)->statusFor($student, $first),
        );
    }

    public function test_orientation_day_never_exceeds_the_total_window(): void
    {
        $service = app(ProgressionService::class);

        config(['ospek.orientation.start_date' => now()->subDays(200)->toDateString(), 'ospek.orientation.days' => 8]);
        $this->assertSame(8, $service->orientationDay());
        $this->assertTrue($service->hasEnded());

        config(['ospek.orientation.start_date' => now()->addDays(5)->toDateString()]);
        $this->assertSame(0, $service->orientationDay());
        $this->assertFalse($service->hasEnded());

        config(['ospek.orientation.start_date' => now()->toDateString()]);
        $this->assertSame(1, $service->orientationDay());
        $this->assertFalse($service->hasEnded());
    }
}