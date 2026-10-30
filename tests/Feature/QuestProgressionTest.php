<?php

namespace Tests\Feature;

use App\Enums\QuestActionType;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestProgressionTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSessionHas('toast');

        $this->assertSame(0, $student->refresh()->xp);
        $this->assertDatabaseMissing('user_quests', [
            'user_id' => $student->id,
            'quest_id' => $locked->id,
            'status' => 'completed',
        ]);
    }

    public function test_completing_a_quest_awards_xp_exactly_once(): void
    {
        $student = $this->student();
        $first = Quest::query()->where('step_number', 1)->firstOrFail();

        $this->actingAs($student)->post("/misi/{$first->id}/selesai", ['confirm' => '1']);

        $questTx = $student->xpTransactions()->where('source_type', 'quest')->where('source_id', $first->id);

        $this->assertSame(1, $questTx->count());
        $this->assertSame($first->xp_reward, (int) $questTx->sum('amount'));

        // Baris progres harus mencatat XP modul ini, bukan meninggalkan angka 0.
        $this->assertDatabaseHas('user_quests', [
            'user_id' => $student->id,
            'quest_id' => $first->id,
            'status' => 'completed',
            'xp_awarded' => $first->xp_reward,
        ]);

        $afterFirst = $student->refresh()->xp;

        // Request ulang tidak boleh menambah poin apa pun.
        $this->actingAs($student)->post("/misi/{$first->id}/selesai", ['confirm' => '1']);

        $this->assertSame($afterFirst, $student->refresh()->xp);
        $this->assertSame(
            1,
            $student->xpTransactions()->where('source_type', 'quest')->where('source_id', $first->id)->count(),
        );
    }

    public function test_completing_a_quest_unlocks_the_next_one(): void
    {
        $student = $this->student();
        $service = app(ProgressionService::class);

        $first = Quest::query()->where('step_number', 1)->firstOrFail();
        $second = Quest::query()->where('step_number', 2)->firstOrFail();

        $this->assertSame(QuestStatus::Locked, $service->statusFor($student, $second));

        $this->actingAs($student)->post("/misi/{$first->id}/selesai", ['confirm' => '1']);

        $this->assertSame(QuestStatus::Completed, $service->statusFor($student->refresh(), $first));
        $this->assertTrue($service->statusFor($student, $second)->isOpen());
    }

    public function test_time_gate_keeps_quests_locked_even_when_previous_is_done(): void
    {
        // Orientasi baru berjalan hari ke-1: modul hari ke-3 harus tetap tertutup.
        config(['ospek.orientation.start_date' => now()->toDateString()]);

        $student = $this->student();
        $service = app(ProgressionService::class);

        $this->actingAs($student)->post('/misi/'.Quest::query()->where('step_number', 1)->value('id').'/selesai', ['confirm' => '1']);

        $third = Quest::query()->where('step_number', 3)->firstOrFail();
        $this->assertSame(QuestStatus::Locked, $service->statusFor($student->refresh(), $third));
    }

    public function test_reflection_quest_requires_a_substantive_note(): void
    {
        $student = $this->student();
        $first = Quest::query()->where('step_number', 1)->firstOrFail();
        $reflectionQuest = Quest::query()->where('step_number', 3)->firstOrFail();

        // Buka jalan sampai modul 3.
        foreach ([1, 2] as $step) {
            $quest = Quest::query()->where('step_number', $step)->firstOrFail();
            $this->actingAs($student)->post("/misi/{$quest->id}/selesai", ['confirm' => '1']);
        }

        $xpBefore = $student->refresh()->xp;

        $this->actingAs($student)
            ->post("/misi/{$reflectionQuest->id}/selesai", ['confirm' => '1', 'reflection' => 'terlalu pendek'])
            ->assertSessionHasErrors('reflection');

        $this->assertSame($xpBefore, $student->refresh()->xp);
        $this->assertNotSame(QuestStatus::Completed, app(ProgressionService::class)->statusFor($student, $reflectionQuest));

        $this->actingAs($student->refresh())->post("/misi/{$reflectionQuest->id}/selesai", [
            'confirm' => '1',
            'reflection' => str_repeat('Saya belajar tentang pentingnya kolaborasi dan perencanaan studi. ', 3),
        ]);

        $this->assertSame(
            QuestStatus::Completed,
            app(ProgressionService::class)->statusFor($student->refresh(), $reflectionQuest),
        );
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
            $payload = ['confirm' => '1'];

            if ($quest->action_type === QuestActionType::Quiz) {
                $this->actingAs($student)->postJson('/kuis/jawab', [
                    'module_code' => $quest->quiz_module_code,
                    'answers' => [1 => 'B', 2 => 'C', 3 => 'C', 4 => 'B', 5 => 'B'],
                ])->assertOk();

                continue;
            }

            if ($quest->action_type === QuestActionType::Reflection) {
                $payload['reflection'] = str_repeat('Catatan refleksi yang cukup panjang untuk lolos validasi. ', 2);
            }

            $this->actingAs($student)->post("/misi/{$quest->id}/selesai", $payload);
            $student->refresh();
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

        // Tombol cetak tidak boleh dirender selama belum memenuhi syarat.
        $response->assertDontSee('onclick="window.print()"', false);
    }
}
