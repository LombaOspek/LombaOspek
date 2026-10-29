<?php

namespace Tests\Feature;

use App\Models\Quest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OspekQuestFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Log Masuk Portal Maba');
        $response->assertSee('OspekQuest');
    }

    public function test_quick_login_redirects_to_dashboard(): void
    {
        $response = $this->get('/quick-login');

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_dashboard_renders_with_gamification_metrics(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Karan Mehra');
        $response->assertSee('Kemajuan Misi Orientasi');
        $response->assertSee('Papan Juara MABA');
    }

    public function test_missions_map_renders_all_quests(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();
        $response = $this->actingAs($user)->get('/misi');

        $response->assertStatus(200);
        $response->assertSee('Peta Kembara Orientasi Siswa');
        $response->assertSee('Sumpah Mahasiswa & Ikrar Integriti');
        $response->assertSee('Kuiz Pengenalan Fakulti & Sistem SKS');
    }

    public function test_completing_a_quest_rewards_xp(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();
        $initialXp = $user->xp;

        $quest = Quest::where('code', 'MOD-04')->first();
        $response = $this->actingAs($user)->post("/misi/{$quest->id}/complete");

        $response->assertSessionHas('toast');
        $user->refresh();
        $this->assertGreaterThan($initialXp, $user->xp);
    }

    public function test_quiz_page_renders_with_questions(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();
        $response = $this->actingAs($user)->get('/kuiz');

        $response->assertStatus(200);
        $response->assertSee('Kuiz Interaktif Harian');
        $response->assertSee('Berapakah had maksimum beban Sistem Kredit Semester (SKS)');
    }

    public function test_submitting_quiz_evaluates_and_awards_xp(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();
        $initialXp = $user->xp;

        $response = $this->actingAs($user)->postJson('/kuiz/submit', [
            'answers' => [
                1 => 'B',
                2 => 'A',
                3 => 'C',
                4 => 'B',
                5 => 'B',
            ],
            'time_taken' => 45,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 5,
            'total' => 5,
            'is_passed' => true,
        ]);

        $user->refresh();
        $this->assertEquals($initialXp + 150, $user->xp);
    }

    public function test_achievements_page_renders_successfully(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();
        $response = $this->actingAs($user)->get('/pencapaian');

        $response->assertStatus(200);
        $response->assertSee('Pencapaian, Lencana & Sijil', false);
        $response->assertSee('Pemburu Misi');
        $response->assertSee('Sijil Tamat Orientasi Siswa Baharu');
    }

    public function test_certificate_page_renders_printable_document(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();
        $response = $this->actingAs($user)->get('/sijil');

        $response->assertStatus(200);
        $response->assertSee('Sijil Tamat Orientasi');
        $response->assertSee($user->name);
        $response->assertSee($user->nim);
    }

    public function test_profile_update_saves_information(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();

        $response = $this->actingAs($user)->post('/profil/update', [
            'name' => 'Karan Mehra Updated',
            'phone' => '+62 899-8877-6655',
            'faculty' => $user->faculty,
            'major' => $user->major,
            'group_name' => 'Alpha Harimau Juara 🐯',
        ]);

        $response->assertRedirect('/profil?tab=profile');
        $user->refresh();
        $this->assertEquals('Karan Mehra Updated', $user->name);
        $this->assertEquals('+62 899-8877-6655', $user->phone);
    }

    public function test_help_ticket_submission_creates_ticket_with_mentor_reply(): void
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first();

        $response = $this->actingAs($user)->post('/bantuan/tiket', [
            'subject' => 'Pertanyaan Jadual Taklimat',
            'category' => 'Akademik & SKS',
            'message' => 'Pukul berapakah sesi taklimat penasihat akademik esok?',
        ]);

        $response->assertRedirect('/profil?tab=helpdesk');
        $this->assertDatabaseHas('help_tickets', [
            'user_id' => $user->id,
            'subject' => 'Pertanyaan Jadual Taklimat',
            'status' => 'answered',
        ]);
    }
}
