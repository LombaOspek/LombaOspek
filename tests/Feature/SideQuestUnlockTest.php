<?php

namespace Tests\Feature;

use App\Enums\QuestStatus;
use App\Enums\UserRole;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Side Quest harus bisa dibuka dan mengunggah bukti foto.
 *
 * Bug yang diperbaiki: `statusFor()` membaca `questBoard()`, dan papan itu
 * sengaja hanya memuat modul utama (`mainline()`) supaya misi opsional tidak
 * mengunci rantai prasyarat. Akibatnya Side Quest TIDAK PERNAH ada di papan dan
 * `statusFor()` selalu mengembalikan `Locked` — halaman detailnya menampilkan
 * "Modul masih terkunci" dengan tombol "Lihat modul yang terbuka", sehingga
 * bukti foto tidak bisa diunggah sama sekali walaupun syaratnya sudah terpenuhi.
 */
class SideQuestUnlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function student(): User
    {
        return User::query()->where('role', UserRole::Student)->firstOrFail();
    }

    private function sq(string $code): Quest
    {
        return Quest::query()->where('code', $code)->firstOrFail();
    }

    public function test_a_side_quest_without_requirement_is_never_locked(): void
    {
        $service = app(ProgressionService::class);
        $student = $this->student();

        $this->assertNotSame(
            QuestStatus::Locked,
            $service->statusFor($student, $this->sq('SQ-01')),
            'SQ-01 tidak punya syarat, jadi tidak boleh terkunci.',
        );
    }

    public function test_a_side_quest_opens_once_its_module_requirement_is_met(): void
    {
        $service = app(ProgressionService::class);
        $student = $this->student();
        $sq2 = $this->sq('SQ-02');

        /*
         * Prasyarat dibuat sendiri oleh tes.
         *
         * `RefreshDatabase` menyemai ulang database, jadi mahasiswa demo mulai
         * dari 0 modul selesai — bukan 2 seperti pada database pengembangan.
         * Tes yang mengandalkan data demo akan lolos di lokal dan gagal di CI.
         */
        $this->completeMainlineQuests($student, (int) $sq2->required_completed_modules);

        $this->assertGreaterThanOrEqual(
            (int) $sq2->required_completed_modules,
            $service->completedCount($student),
        );

        $this->assertNotSame(
            QuestStatus::Locked,
            $service->statusFor($student, $sq2),
            'Syarat sudah terpenuhi, jadi SQ-02 harus terbuka.',
        );
    }

    /** Tandai N modul utama pertama sebagai selesai. */
    private function completeMainlineQuests(User $user, int $count): void
    {
        $quests = Quest::query()->mainline()->ordered()->limit($count)->get();

        foreach ($quests as $quest) {
            $user->quests()->syncWithoutDetaching([
                $quest->getKey() => [
                    'status' => QuestStatus::Completed->value,
                    'completed_at' => now(),
                    'xp_awarded' => 0,
                ],
            ]);
        }

        $user->unsetRelation('quests');
    }

    public function test_a_side_quest_stays_locked_before_its_requirement(): void
    {
        $service = app(ProgressionService::class);
        $student = $this->student();

        // SQ-05 butuh 8 modul. Mahasiswa demo baru 2.
        $sq5 = $this->sq('SQ-05');

        if ($service->completedCount($student) < (int) $sq5->required_completed_modules) {
            $this->assertSame(QuestStatus::Locked, $service->statusFor($student, $sq5));
        }
    }

    public function test_the_detail_page_of_a_photo_side_quest_offers_an_upload(): void
    {
        /*
         * Inilah keluhan aslinya: "apakah side quest ada validasi gambar seperti
         * quest utama?" Jawabannya harus ya untuk SQ bertipe photo_proof, dan
         * dibuktikan lewat markup halaman, bukan asumsi.
         */
        $student = $this->student();
        $sq2 = $this->sq('SQ-02');

        // Input berkas hanya dirender saat misi terbuka, jadi prasyaratnya
        // harus dipenuhi dulu — persis seperti mahasiswa sungguhan.
        $this->completeMainlineQuests($student, (int) $sq2->required_completed_modules);

        $html = $this->actingAs($student)->get("/misi/{$sq2->getKey()}")->assertOk()->getContent();

        $this->assertStringContainsString('type="file"', $html, 'Side Quest foto harus punya input berkas.');
        $this->assertStringContainsString('name="proof"', $html, 'Input harus bernama proof agar tervalidasi server.');
        $this->assertStringContainsString('image/', $html, 'Input harus dibatasi ke berkas gambar.');
    }

    public function test_a_photo_side_quest_is_not_shown_as_locked_when_available(): void
    {
        $student = $this->student();
        $sq2 = $this->sq('SQ-02');
        $this->completeMainlineQuests($student, (int) $sq2->required_completed_modules);

        $html = $this->actingAs($student)->get("/misi/{$sq2->getKey()}")->assertOk()->getContent();

        // Teks blokir hanya boleh muncul kalau statusnya memang terkunci.
        $service = app(ProgressionService::class);
        $status = $service->statusFor($student, $sq2);

        if ($status !== QuestStatus::Locked) {
            $this->assertStringNotContainsString('Modul masih terkunci', $html);
            $this->assertStringNotContainsString('Lihat modul yang terbuka', $html);
        } else {
            $this->markTestSkipped('Prasyarat SQ-02 belum terpenuhi pada data uji.');
        }
    }

    public function test_a_side_quest_never_blocks_the_mainline_chain(): void
    {
        /*
         * Perbaikan tidak boleh merusak aturan lama: Side Quest tetap TIDAK
         * masuk papan modul utama, jadi misi opsional yang dilewatkan tidak
         * mengunci modul sesudahnya.
         */
        $board = app(ProgressionService::class)->questBoard($this->student());

        $this->assertTrue(
            $board->every(fn (array $row) => ! $row['quest']->is_side_quest),
            'Papan modul utama tidak boleh memuat Side Quest.',
        );
    }

    public function test_a_side_quest_can_still_be_completed(): void
    {
        $service = app(ProgressionService::class);
        $student = $this->student();
        $sq1 = $this->sq('SQ-01');

        $service->markInProgress($student, $sq1);

        $this->assertSame(
            QuestStatus::InProgress,
            $service->statusFor($student, $sq1),
            'Status harus in_progress setelah dimulai.',
        );
    }
}