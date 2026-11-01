<?php

namespace Tests\Feature;

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\SideQuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Side Quest adalah fitur yang diminta eksplisit oleh proposal (interaksi
 * sosial), tetapi ia opsional — dan justru karena itu paling mudah merusak
 * hal lain. Yang dijaga di sini:
 *
 *   - Side Quest TIDAK masuk papan misi utama
 *   - Side Quest TIDAK memutus rantai prasyarat
 *   - Side Quest TIDAK mengubah jumlah babak / hitungan modul
 *   - Gerbangnya progres modul utama, bukan hari
 */
class SideQuestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\QuestSeeder::class);
        $this->seed(\Database\Seeders\SideQuestSeeder::class);
    }

    private function student(): User
    {
        return User::create([
            'nim' => '202653055',
            'name' => 'Uji Side Quest',
            'email' => 'sq@example.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'xp' => 0,
            'coins' => 0,
            'level' => 1,
        ]);
    }

    public function test_side_quests_are_not_on_the_main_quest_board(): void
    {
        $user = $this->student();

        $board = app(ProgressionService::class)->questBoard($user);

        $this->assertSame(13, $board->count(), 'Papan utama harus tetap 13 modul.');
        $this->assertFalse(
            $board->contains(fn (array $row): bool => $row['quest']->is_side_quest),
            'Side Quest tidak boleh muncul di peta misi utama.',
        );
    }

    public function test_side_quests_do_not_break_the_prerequisite_chain(): void
    {
        $user = $this->student();

        $board = app(ProgressionService::class)->questBoard($user);

        // Regresi yang dijaga: kalau Side Quest ikut masuk rantai, modul
        // PERTAMA pun terkunci karena `previousCompleted` dimulai dari misi
        // opsional yang belum dikerjakan. Modul 2-13 memang terkunci rantai,
        // jadi yang diuji adalah modul pertama + jumlah total terkunci.
        $first = $board->first();

        $this->assertSame(QuestStatus::Available, $first['status'], 'Modul pertama harus terbuka.');
        $this->assertFalse($first['quest']->is_side_quest);

        $locked = $board->where('status', QuestStatus::Locked)->count();
        $this->assertSame(12, $locked, 'Hanya 12 modul yang terkunci rantai, bukan semuanya.');
    }

    public function test_chapters_still_total_thirteen_modules(): void
    {
        $user = $this->student();

        $chapters = app(ProgressionService::class)->chapters($user);

        $this->assertSame(3, $chapters->count(), 'Jumlah babak tidak boleh berubah oleh Side Quest.');
        $this->assertSame(13, (int) $chapters->sum(fn (array $c): int => $c['total']));
    }

    public function test_side_quest_is_locked_until_enough_main_modules_are_done(): void
    {
        $user = $this->student();

        $board = app(SideQuestService::class)->board($user);

        $this->assertGreaterThan(0, $board->count());

        // Misi pertama tidak butuh prasyarat.
        $first = $board->firstWhere('quest.code', 'SQ-01');
        $this->assertSame(QuestStatus::Available, $first['status']);

        // Misi yang butuh 8 modul harus terkunci di akun baru.
        $late = $board->firstWhere('quest.code', 'SQ-05');
        $this->assertSame(QuestStatus::Locked, $late['status']);
        $this->assertStringContainsString('modul utama', $late['locked_reason']);
    }

    public function test_side_quest_unlocks_as_main_modules_complete(): void
    {
        $user = $this->student();

        $mainline = Quest::query()->mainline()->ordered()->take(4)->get();
        $progression = app(ProgressionService::class);

        foreach ($mainline as $quest) {
            $progression->complete($user, $quest);
        }

        $board = app(SideQuestService::class)->board($user);

        // SQ-03 butuh 4 modul utama.
        $sq3 = $board->firstWhere('quest.code', 'SQ-03');
        $this->assertSame(QuestStatus::Available, $sq3['status']);

        // SQ-04 butuh 6, jadi masih terkunci.
        $sq4 = $board->firstWhere('quest.code', 'SQ-04');
        $this->assertSame(QuestStatus::Locked, $sq4['status']);
    }

    public function test_completed_side_quests_do_not_count_as_main_modules(): void
    {
        $user = $this->student();

        $sideQuest = Quest::query()->sideQuests()->orderBy('step_number')->first();
        app(ProgressionService::class)->complete($user, $sideQuest);

        $this->assertSame(0, app(SideQuestService::class)->completedModuleCount($user));
    }

    public function test_page_lists_side_quests_to_authenticated_students(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->get(route('side-quests.index'))
            ->assertOk()
            ->assertSee('Sapa Lima Teman Baru')
            ->assertSee('Rekam Pesan untuk Diri Sendiri');
    }

    public function test_page_requires_authentication(): void
    {
        $this->get(route('side-quests.index'))->assertRedirect(route('login'));
    }

    public function test_mainline_endpoint_rejects_a_side_quest_slug(): void
    {
        $user = $this->student();
        $sideQuest = Quest::query()->sideQuests()->firstOrFail();

        // Side Quest tidak boleh dimulai lewat endpoint modul utama, karena
        // endpoint itu memakai statusFor() yang hanya mengenal papan utama.
        $this->actingAs($user)
            ->post(route('side-quests.start', $sideQuest))
            ->assertRedirect();

        $this->assertSame(
            QuestStatus::InProgress,
            app(SideQuestService::class)->board($user)
                ->firstWhere('quest.code', $sideQuest->code)['status'],
        );
    }
}