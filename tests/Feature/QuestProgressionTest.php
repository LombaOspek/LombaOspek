<?php

namespace Tests\Feature;

use App\Enums\QuestActionType;
use App\Enums\QuestStatus;
use App\Enums\VerificationMethod;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CompletesQuests;
use Tests\TestCase;

class QuestProgressionTest extends TestCase
{
    use CompletesQuests, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Seluruh modul dianggap sudah waktunya dibuka, hanya urutan yang diuji.
        config(['ospek.orientation.start_date' => now()->subDays(30)->toDateString()]);
    }

    private function student(): User
    {
        return User::query()->where('nim', '202653033')->firstOrFail();
    }

    public function test_locked_quest_cannot_be_completed_directly(): void
    {
        $student = $this->student();
        $locked = Quest::query()->where('step_number', 5)->firstOrFail();

        $this->actingAs($student)
            ->post("/misi/{$locked->id}/selesai", ['confirm' => '1'])
            ->assertRedirect();

        $this->assertSame(0, $student->refresh()->xp);
        $this->assertNotSame(
            QuestStatus::Completed,
            app(ProgressionService::class)->statusFor($student, $locked),
        );
    }

    /**
     * Inti anti-curang: modul bertoken TIDAK boleh selesai lewat tombol biasa.
     */
    public function test_verification_quest_cannot_be_completed_without_proof(): void
    {
        $student = $this->student();
        $tokenQuest = Quest::query()->where('verification_method', VerificationMethod::Token)->firstOrFail();

        // Buka jalan sampai modul bertoken pertama (MOD-02).
        $this->advanceTo($student, $tokenQuest->step_number);

        $before = $student->refresh()->xp;

        $response = $this->actingAs($student)->postJson("/misi/{$tokenQuest->id}/selesai", ['confirm' => '1']);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'requires_verification' => true,
            'method' => 'token',
        ]);

        $this->assertSame($before, $student->refresh()->xp);
        $this->assertNotSame(
            QuestStatus::Completed,
            app(ProgressionService::class)->statusFor($student, $tokenQuest),
        );
    }

    public function test_completing_a_quest_awards_xp_exactly_once(): void
    {
        $student = $this->student();
        $tokenQuest = Quest::query()->where('verification_method', VerificationMethod::Token)->firstOrFail();

        $this->advanceTo($student, $tokenQuest->step_number);
        $before = $student->refresh()->xp;

        $this->redeemKnownToken($student, $tokenQuest);

        $questTx = $student->xpTransactions()
            ->where('source_type', 'quest')
            ->where('source_id', $tokenQuest->id);

        $this->assertSame(1, $questTx->count());
        $this->assertSame($before + $tokenQuest->xp_reward, $student->refresh()->xp);

        // Klaim ulang dengan token yang sama harus ditolak.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(VerificationService::class)->redeemToken($student, $tokenQuest->refresh(), 'TES-TOKEN-26');
    }

    public function test_completing_a_quest_unlocks_the_next_one(): void
    {
        $student = $this->student();
        $service = app(ProgressionService::class);

        $first = Quest::query()->where('step_number', 1)->firstOrFail();
        $second = Quest::query()->where('step_number', 2)->firstOrFail();

        $this->assertSame(QuestStatus::Locked, $service->statusFor($student, $second));

        $this->completeQuestProperly($student, $first);

        $this->assertSame(QuestStatus::Completed, $service->statusFor($student->refresh(), $first));
        $this->assertTrue($service->statusFor($student, $second)->isOpen());
    }

    public function test_time_gate_keeps_quests_locked_even_when_previous_is_done(): void
    {
        config(['ospek.orientation.start_date' => now()->toDateString()]);

        $student = $this->student();
        $service = app(ProgressionService::class);

        $first = Quest::query()->where('step_number', 1)->firstOrFail();
        $this->completeQuestProperly($student, $first);

        $third = Quest::query()->where('step_number', 3)->firstOrFail();
        $this->assertSame(QuestStatus::Locked, $service->statusFor($student->refresh(), $third));
    }

    public function test_reflection_quest_requires_a_substantive_note(): void
    {
        $student = $this->student();
        $reflectionQuest = Quest::query()->where('action_type', QuestActionType::Reflection)->firstOrFail();

        $this->advanceTo($student, $reflectionQuest->step_number);
        $before = $student->refresh()->xp;

        $this->actingAs($student->refresh())
            ->post("/misi/{$reflectionQuest->id}/selesai", ['confirm' => '1', 'reflection' => 'pendek'])
            ->assertSessionHasErrors('reflection');

        $this->assertSame($before, $student->refresh()->xp);
    }

    public function test_completion_requires_confirmation_checkbox(): void
    {
        $student = $this->student();
        $first = Quest::query()->where('step_number', 1)->firstOrFail();

        $this->actingAs($student)
            ->post("/misi/{$first->id}/selesai", [])
            ->assertSessionHasErrors('confirm');
    }

    public function test_finishing_all_quests_marks_orientation_complete(): void
    {
        $student = $this->student();

        foreach (Quest::query()->ordered()->get() as $quest) {
            $this->completeQuestProperly($student, $quest);
        }

        $this->assertNotNull($student->refresh()->orientation_completed_at);
        $this->actingAs($student)->get('/sijil')->assertOk()->assertSee('Sertifikat Orientasi');
    }

    public function test_certificate_is_locked_before_all_quests_are_done(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->get('/sijil');

        $response->assertOk()
            ->assertSee('Terkunci')
            ->assertSee('Sertifikat baru dapat dicetak setelah seluruh');

        $response->assertDontSee('onclick="window.print()"', false);
    }

    public function test_quests_outside_student_major_are_hidden(): void
    {
        $student = $this->student();

        $targeted = Quest::query()->where('step_number', 1)->firstOrFail();
        $targeted->forceFill([
            'target_scope' => 'major',
            'target_study_programs' => ['Informatika'],
        ])->save();

        $board = app(ProgressionService::class)->questBoard($student->refresh());

        $this->assertNull($board->first(fn (array $row): bool => $row['quest']->is($targeted)));
    }

    public function test_draft_quests_never_appear_on_the_board(): void
    {
        $student = $this->student();

        $quest = Quest::query()->where('step_number', 1)->firstOrFail();
        $quest->forceFill(['status' => 'draft'])->save();

        $board = app(ProgressionService::class)->questBoard($student->refresh());

        $this->assertNull($board->first(fn (array $row): bool => $row['quest']->is($quest)));
    }
}