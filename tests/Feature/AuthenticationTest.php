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

    public function test_student_can_login_with_email(): void
    {
        $user = User::query()->where('nim', '202653033')->firstOrFail();

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected_without_leaking_which_field_failed(): void
    {
        $this->post('/login', [
            'identity' => '202653033',
            'password' => 'salah-total',
        ])->assertSessionHasErrors('identity');

        $this->assertGuest();
    }

    public function test_unknown_identity_is_rejected(): void
    {
        $this->post('/login', [
            'identity' => '999999999',
            'password' => 'password',
        ])->assertSessionHasErrors('identity');

        $this->assertGuest();
    }

    public function test_quick_login_bypass_route_no_longer_exists(): void
    {
        // Rute lama GET /quick-login meloloskan siapa pun masuk tanpa kredensial.
        $this->get('/quick-login')->assertNotFound();
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post('/login', [
                'identity' => '202653033',
                'password' => 'salah',
            ]);
        }

        $this->post('/login', [
            'identity' => '202653033',
            'password' => 'salah',
        ])->assertStatus(429);
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::query()->where('nim', '202653033')->firstOrFail();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_mentor_routes_are_forbidden_for_students(): void
    {
        $student = User::query()->where('nim', '202653033')->firstOrFail();

        $this->actingAs($student)->get('/mentor/tiket')->assertForbidden();
    }

    public function test_mentor_can_open_ticket_inbox(): void
    {
        $mentor = User::query()->where('role', UserRole::Mentor)->firstOrFail();

        $this->actingAs($mentor)->get('/mentor/tiket')->assertOk();
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

        // Acara jauh di masa lalu: hari harus berhenti di total, bukan terus naik.
        config(['ospek.orientation.start_date' => now()->subDays(200)->toDateString(), 'ospek.orientation.days' => 8]);

        $this->assertSame(8, $service->orientationDay());
        $this->assertTrue($service->hasEnded());

        // Sebelum mulai.
        config(['ospek.orientation.start_date' => now()->addDays(5)->toDateString()]);

        $this->assertSame(0, $service->orientationDay());
        $this->assertFalse($service->hasEnded());

        // Hari pertama.
        config(['ospek.orientation.start_date' => now()->toDateString()]);

        $this->assertSame(1, $service->orientationDay());
        $this->assertFalse($service->hasEnded());
    }
}
