<?php

namespace Tests\Feature;

use App\Enums\QuestActionType;
use App\Enums\QuestStatus;
use App\Models\Achievement;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    private const CORRECT = [1 => 'B', 2 => 'C', 3 => 'C', 4 => 'B', 5 => 'B'];

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

    /**
     * Membuka modul 4 dengan menyelesaikan modul 1-3 lebih dulu.
     */
    private function unlockQuiz(User $student): void
    {
        foreach ([1, 2, 3] as $step) {
            $quest = Quest::query()->where('step_number', $step)->firstOrFail();
            $payload = ['confirm' => '1'];

            if ($quest->action_type === QuestActionType::Reflection) {
                $payload['reflection'] = str_repeat('Refleksi cukup panjang untuk memenuhi validasi modul. ', 2);
            }

            $this->actingAs($student)->post("/misi/{$quest->id}/selesai", $payload);
            $student->refresh();
        }
    }

    public function test_quiz_is_not_accessible_while_gated(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->get('/kuis/MOD-04')
            ->assertRedirect('/misi');
    }

    public function test_quiz_page_renders_after_prerequisites(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $this->actingAs($student)
            ->get('/kuis/MOD-04')
            ->assertOk()
            ->assertSee('soal')
            ->assertSee('Baca sebelum mulai');
    }

    public function test_start_returns_server_side_expiry(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $this->actingAs($student)
            ->postJson('/kuis/mulai', ['module_code' => 'MOD-04'])
            ->assertOk()
            ->assertJsonStructure(['success', 'started_at', 'expires_at', 'time_limit', 'attempt_number', 'remaining_attempts'])
            ->assertJson(['success' => true, 'time_limit' => 300]);
    }

    public function test_perfect_score_passes_and_awards_xp_once(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $response = $this->actingAs($student)->postJson('/kuis/jawab', [
            'module_code' => 'MOD-04',
            'answers' => self::CORRECT,
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'score' => 5,
            'total' => 5,
            'percentage' => 100,
            'is_passed' => true,
        ]);

        // 30+30+30+40+40 = 170 XP dari bank soal.
        $this->assertSame(170, $response->json('xp_earned'));

        $student->refresh();
        $quizXp = $student->xpTransactions()->where('source_type', 'quiz')->sum('amount');

        $this->assertSame(170, (int) $quizXp);
        $this->assertSame(1, $student->xpTransactions()->where('source_type', 'quiz')->count());
    }

    public function test_repeating_a_passed_quiz_does_not_award_xp_again(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $this->actingAs($student)->postJson('/kuis/jawab', [
            'module_code' => 'MOD-04',
            'answers' => self::CORRECT,
        ])->assertOk();

        $afterFirst = $student->refresh()->xp;

        $this->actingAs($student)->postJson('/kuis/jawab', [
            'module_code' => 'MOD-04',
            'answers' => self::CORRECT,
        ])->assertOk();

        $this->assertSame($afterFirst, $student->refresh()->xp);
    }

    public function test_failing_score_does_not_complete_the_module(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $this->actingAs($student)->postJson('/kuis/jawab', [
            'module_code' => 'MOD-04',
            'answers' => [1 => 'A', 2 => 'A', 3 => 'A', 4 => 'A', 5 => 'B'],
        ])->assertOk()->assertJson(['is_passed' => false, 'xp_earned' => 0]);

        $quizQuest = Quest::query()->where('quiz_module_code', 'MOD-04')->firstOrFail();
        $this->assertNotSame(
            QuestStatus::Completed,
            app(ProgressionService::class)->statusFor($student->refresh(), $quizQuest),
        );
    }

    public function test_passing_quiz_completes_module_and_unlocks_next(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $this->actingAs($student)->postJson('/kuis/jawab', [
            'module_code' => 'MOD-04',
            'answers' => self::CORRECT,
        ])->assertOk();

        $student->refresh();
        $service = app(ProgressionService::class);

        $quizQuest = Quest::query()->where('quiz_module_code', 'MOD-04')->firstOrFail();
        $next = Quest::query()->where('step_number', $quizQuest->step_number + 1)->firstOrFail();

        $this->assertSame(QuestStatus::Completed, $service->statusFor($student, $quizQuest));
        $this->assertTrue($service->statusFor($student, $next)->isOpen());
    }

    public function test_attempt_limit_is_enforced(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $max = (int) config('ospek.quiz.max_attempts');

        for ($i = 0; $i < $max; $i++) {
            $this->actingAs($student)->postJson('/kuis/jawab', [
                'module_code' => 'MOD-04',
                'answers' => [1 => 'A', 2 => 'A', 3 => 'A', 4 => 'A', 5 => 'A'],
            ])->assertOk();
        }

        $this->attachFreshSession();
        $student->refresh();

        $this->actingAs($student)->postJson('/kuis/jawab', [
            'module_code' => 'MOD-04',
            'answers' => self::CORRECT,
        ])->assertStatus(422);
    }

    public function test_expired_session_is_rejected_by_server_clock(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        // Sesi dimulai 40 menit lalu: melampaui batas 5 menit + toleransi.
        $this->actingAs($student)
            ->withSession(['quiz.started_at.MOD-04' => now()->subMinutes(40)->toIso8601String()])
            ->postJson('/kuis/jawab', [
                'module_code' => 'MOD-04',
                'answers' => self::CORRECT,
            ])
            ->assertStatus(422);

        $this->assertSame(0, $student->refresh()->quizAttempts()->count());
    }

    public function test_invalid_option_letter_is_validation_error(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $this->actingAs($student)->postJson('/kuis/jawab', [
            'module_code' => 'MOD-04',
            'answers' => [1 => 'Z', 2 => 'B'],
        ])->assertStatus(422);
    }

    public function test_perfect_quiz_unlocks_the_scholar_achievement(): void
    {
        $student = $this->student();
        $this->unlockQuiz($student);

        $this->actingAs($student)->postJson('/kuis/jawab', [
            'module_code' => 'MOD-04',
            'answers' => self::CORRECT,
        ])->assertOk();

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $student->id,
            'achievement_id' => Achievement::query()->where('code', 'ach-03')->value('id'),
            'is_unlocked' => true,
        ]);
    }

    private function attachFreshSession(): void
    {
        $this->flushSession();
    }
}
