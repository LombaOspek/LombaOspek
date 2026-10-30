<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Enums\VerificationMethod;
use App\Models\Achievement;
use App\Models\HelpTicket;
use App\Models\Quest;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CompletesQuests;
use Tests\TestCase;

class GamificationIntegrityTest extends TestCase
{
    use CompletesQuests, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['ospek.orientation.start_date' => now()->subDays(30)->toDateString()]);
    }

    private function student(): User
    {
        return User::query()->where('nim', '202653033')->firstOrFail();
    }

    public function test_xp_award_is_idempotent_on_the_same_key(): void
    {
        $student = $this->student();
        $service = app(XpService::class);

        $this->assertSame(100, $service->award($student, 100, 'test', 1, 'Uji', 'test-key-1'));
        $this->assertNull($service->award($student, 100, 'test', 1, 'Uji lagi', 'test-key-1'));
        $this->assertSame(100, $student->refresh()->xp);
        $this->assertSame(1, $student->xpTransactions()->where('idempotency_key', 'test-key-1')->count());
    }

    public function test_xp_ledger_matches_the_user_column(): void
    {
        $student = $this->student();
        $quest = Quest::query()->where('step_number', 1)->firstOrFail();

        $this->completeQuestProperly($student, $quest);

        $audit = app(XpService::class)->audit($student->refresh());

        $this->assertTrue($audit['consistent'], 'Buku besar XP harus sama dengan kolom users.xp.');
    }

    public function test_level_follows_configured_xp_bands(): void
    {
        $service = app(XpService::class);

        $this->assertSame(1, $service->levelForXp(0));
        $this->assertSame(2, $service->levelForXp(300));
        $this->assertSame(3, $service->levelForXp(750));
        $this->assertSame(4, $service->levelForXp(1400));
        $this->assertSame(7, $service->levelForXp(9999));
    }

    public function test_settings_toggles_can_be_turned_off(): void
    {
        $student = $this->student();
        $this->assertTrue($student->sound_effects);

        $this->actingAs($student)->post('/profil/tetapan', [])->assertRedirect('/profil?tab=settings');

        $student->refresh();
        $this->assertFalse($student->sound_effects);
        $this->assertFalse($student->email_notifications);
        $this->assertFalse($student->whatsapp_notifications);
    }

    public function test_settings_toggles_can_be_turned_on(): void
    {
        $student = $this->student();
        $student->forceFill(['sound_effects' => false])->save();

        $this->actingAs($student)->post('/profil/tetapan', ['sound_effects' => '1']);

        $this->assertTrue($student->refresh()->sound_effects);
    }

    public function test_help_ticket_starts_open_without_fake_reply(): void
    {
        $student = $this->student();

        $this->actingAs($student)->post('/bantuan/tiket', [
            'subject' => 'Modul lima tidak terbuka',
            'category' => 'Akademik & SKS',
            'message' => 'Saya sudah menyelesaikan modul empat tetapi modul lima masih terkunci.',
        ])->assertRedirect('/profil?tab=helpdesk');

        $ticket = HelpTicket::query()->where('user_id', $student->id)->firstOrFail();

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->reply);
    }

    public function test_officer_reply_is_attributed(): void
    {
        $student = $this->student();
        $officer = User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();

        $ticket = HelpTicket::create([
            'user_id' => $student->id,
            'subject' => 'Pertanyaan jadwal',
            'category' => 'Umum',
            'message' => 'Kapan sesi luring kelompok empat dimulai besok pagi?',
            'status' => TicketStatus::Open,
        ]);

        $this->actingAs($officer)->post("/lo/tiket/{$ticket->id}/balas", [
            'reply' => 'Sesi dimulai pukul 08.00 di Ruang 4B, hadir 15 menit lebih awal.',
            'status' => 'answered',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame(TicketStatus::Answered, $ticket->status);
        $this->assertSame($officer->id, $ticket->answered_by);
        $this->assertNotNull($ticket->answered_at);
    }

    public function test_student_cannot_answer_their_own_ticket(): void
    {
        $student = $this->student();

        $ticket = HelpTicket::create([
            'user_id' => $student->id,
            'subject' => 'Percobaan eskalasi',
            'category' => 'Umum',
            'message' => 'Saya mencoba menjawab tiket saya sendiri tanpa hak LO.',
            'status' => TicketStatus::Open,
        ]);

        $this->actingAs($student)
            ->post("/lo/tiket/{$ticket->id}/balas", ['reply' => 'Balasan palsu dari mahasiswa.'])
            ->assertForbidden();

        $this->assertNull($ticket->refresh()->reply);
    }

    public function test_achievement_progress_is_derived_from_real_data(): void
    {
        $student = $this->student();
        $achievement = Achievement::query()->where('code', 'ach-01')->firstOrFail();

        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $student->id,
            'achievement_id' => $achievement->id,
            'is_unlocked' => true,
        ]);

        $this->completeQuestProperly($student, Quest::query()->where('step_number', 1)->firstOrFail());

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $student->id,
            'achievement_id' => $achievement->id,
            'is_unlocked' => true,
        ]);
    }

    public function test_achievement_bonus_xp_is_awarded_once(): void
    {
        $student = $this->student();
        $achievement = Achievement::query()->where('code', 'ach-01')->firstOrFail();

        $this->completeQuestProperly($student, Quest::query()->where('step_number', 1)->firstOrFail());
        $afterUnlock = $student->refresh()->xp;

        $this->actingAs($student)->get('/pencapaian')->assertOk();
        $this->actingAs($student)->get('/pencapaian')->assertOk();

        $this->assertSame($afterUnlock, $student->refresh()->xp);
        $this->assertSame(
            1,
            $student->xpTransactions()->where('source_type', 'achievement')->where('source_id', $achievement->id)->count(),
        );
    }

    public function test_leaderboard_ranks_by_xp_descending(): void
    {
        $student = $this->student();

        $this->actingAs($student)->get('/peringkat')->assertOk()->assertSee('Peringkat kampus');

        $top = app(\App\Services\LeaderboardService::class)->top(5);
        $xpValues = $top->pluck('user.xp')->all();
        $sorted = $xpValues;
        rsort($sorted);

        $this->assertSame($sorted, $xpValues);
    }

    public function test_certificate_code_is_stable_and_verifiable(): void
    {
        $student = $this->student();
        $code = $student->certificate_code;

        $this->assertSame($code, $student->fresh()->certificate_code);
        $this->assertStringStartsWith('OSQ-', $code);
        $this->assertNull(app(CertificateService::class)->verify($code));

        $this->get('/verifikasi/'.$code)->assertOk()->assertSee('Kode tidak ditemukan');
    }

    public function test_verification_page_hides_private_data_for_valid_codes(): void
    {
        $student = $this->student();
        $student->forceFill(['orientation_completed_at' => now()])->save();

        $this->get('/verifikasi/'.$student->certificate_code)
            ->assertOk()
            ->assertSee('Sertifikat sah')
            ->assertSee($student->nim)
            ->assertDontSee($student->email);
    }

    public function test_profile_update_cannot_change_nim_or_role(): void
    {
        $student = $this->student();
        $originalNim = $student->nim;

        $this->actingAs($student)->post('/profil', [
            'name' => 'Nama Diperbarui',
            'nim' => '000000000',
            'role' => 'admin',
            'phone' => '0812-0000-1111',
        ]);

        $student->refresh();

        $this->assertSame('Nama Diperbarui', $student->name);
        $this->assertSame($originalNim, $student->nim);
        $this->assertSame(UserRole::Student, $student->role);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $student = $this->student();

        $this->actingAs($student)->post('/profil/sandi', [
            'current_password' => 'salah',
            'password' => 'kata-sandi-baru-2026',
            'password_confirmation' => 'kata-sandi-baru-2026',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($student)->post('/profil/sandi', [
            'current_password' => 'password',
            'password' => 'kata-sandi-baru-2026',
            'password_confirmation' => 'kata-sandi-baru-2026',
        ])->assertRedirect('/profil?tab=settings');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('kata-sandi-baru-2026', $student->refresh()->password));
    }

    public function test_daily_activity_increments_the_streak_once_per_day(): void
    {
        $student = $this->student();
        $this->assertSame(0, $student->streak_days);

        $this->actingAs($student)->get('/dashboard');
        $this->assertSame(1, $student->refresh()->streak_days);

        $this->actingAs($student)->get('/dashboard');
        $this->assertSame(1, $student->refresh()->streak_days);
    }
}