<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Quest;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Models\UserQuest;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tugas sisa plan: panel anggota gugus, Gugus Wars live, bendera finish,
 * admin Bursa, dan Side Quest wajib untuk sertifikat.
 */
class RemainingPlanTest extends TestCase
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

    /* ---------------- 2a: panel anggota gugus ---------------- */

    public function test_group_detail_page_lists_members_and_capacity(): void
    {
        $group = Group::query()->whereHas('members')->firstOrFail();

        $html = $this->actingAs($this->admin())
            ->get("/admin/gugus/{$group->id}")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($group->name, $html);
        $this->assertStringContainsString('anggota', $html);
        $this->assertStringContainsString('data-group-member-form', $html);
    }

    /**
     * Prodi yang punya lebih dari satu gugus: hanya di situ ada kandidat,
     * karena mahasiswa sejurusan yang belum masuk gugus ini bisa dipindahkan.
     */
    private function groupWithCandidates(): Group
    {
        return Group::query()
            ->whereIn('study_program', function ($q): void {
                $q->select('study_program')
                    ->from('groups')
                    ->groupBy('study_program')
                    ->havingRaw('count(*) > 1');
            })
            ->firstOrFail();
    }

    public function test_candidates_only_include_the_groups_own_major(): void
    {
        $group = $this->groupWithCandidates();

        $response = $this->actingAs($this->admin())
            ->getJson("/admin/gugus/{$group->id}/kandidat")
            ->assertOk();

        $majors = User::query()
            ->whereIn('id', collect($response->json('candidates'))->pluck('id'))
            ->pluck('major')
            ->unique();

        $this->assertSame(
            [$group->study_program],
            $majors->all(),
            'Kandidat harus sejurusan dengan gugus.',
        );
    }

    public function test_member_can_be_moved_into_a_group(): void
    {
        $group = $this->groupWithCandidates();

        $outsider = User::query()
            ->where('role', UserRole::Student)
            ->where('major', $group->study_program)
            ->where(fn ($q) => $q->whereNull('group_id')->orWhere('group_id', '!=', $group->id))
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->post("/admin/gugus/{$group->id}/anggota", ['user_ids' => [$outsider->id]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($group->id, $outsider->fresh()->group_id);
    }

    public function test_adding_a_student_from_another_major_is_rejected(): void
    {
        $group = $this->groupWithCandidates();

        $wrongMajor = User::query()
            ->where('role', UserRole::Student)
            ->where('major', '!=', $group->study_program)
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->post("/admin/gugus/{$group->id}/anggota", ['user_ids' => [$wrongMajor->id]])
            ->assertSessionHasErrors('user_ids');

        $this->assertNotSame($group->id, $wrongMajor->fresh()->group_id);
    }

    public function test_removing_a_member_keeps_the_account_and_its_history(): void
    {
        $group = Group::query()->whereHas('members')->firstOrFail();
        $member = User::query()->where('group_id', $group->id)->firstOrFail();

        // Riwayat XP harus selamat: mengeluarkan dari gugus BUKAN menghapus akun.
        \App\Models\XpTransaction::create([
            'user_id' => $member->id,
            'amount' => 50,
            'balance_after' => 50,
            'source_type' => 'quest',
            'source_id' => 1,
            'description' => 'Riwayat sebelum dikeluarkan',
            'idempotency_key' => 'member:remove:1',
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/gugus/{$group->id}/anggota/{$member->id}")
            ->assertRedirect();

        $fresh = $member->fresh();

        $this->assertNull($fresh->group_id, 'Mahasiswa harus keluar dari gugus.');
        $this->assertNotNull($fresh, 'Akun tidak boleh terhapus.');
        $this->assertSame(1, \App\Models\XpTransaction::where('user_id', $member->id)->count());
    }

    /* ---------------- 2e: Gugus Wars live ---------------- */

    public function test_wars_widget_exposes_polling_hooks(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $html = $this->actingAs($student)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-gugus-wars', $html);
        $this->assertStringContainsString('data-wars-endpoint', $html);
        $this->assertStringContainsString('data-war-arena', $html);
        // Badge LIVE redup sampai polling pertama berhasil.
        $this->assertStringContainsString('data-wars-live', $html);
    }

    public function test_summary_endpoint_returns_wars_data(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $response = $this->actingAs($student)->getJson('/misi-seru/ringkasan')->assertOk();

        $this->assertArrayHasKey('gugus_wars', $response->json());
        $this->assertArrayHasKey('ratio', $response->json('gugus_wars'));
    }

    /* ---------------- 3a: bendera finish ---------------- */

    public function test_the_last_node_renders_a_finish_flag(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $html = $this->actingAs($student)->get('/misi')->assertOk()->getContent();

        $this->assertStringContainsString('quest-finish-flag', $html);

        // Hanya SATU node yang memakai bendera: ujung lintasan.
        $this->assertSame(1, substr_count($html, 'class="quest-finish-flag"'));
    }

    public function test_the_map_shows_one_mascot_per_chapter(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $html = $this->actingAs($student)->get('/misi')->assertOk()->getContent();

        // 4 babak = 4 maskot, bukan 3 untuk seluruh peta.
        $this->assertSame(4, substr_count($html, 'class="quest-mascot"'));
    }

    /* ---------------- 3b: admin Bursa ---------------- */

    public function test_admin_bursa_lists_rewards_and_offers_an_upload_form(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/bursa')->assertOk()->getContent();

        $this->assertStringContainsString('Kelola Bursa Hadiah', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('name="image"', $html);
    }

    public function test_admin_can_create_a_reward(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/bursa', [
                'code' => 'MUG-02',
                'name' => 'Mug Keramik',
                'category' => 'merchandise',
                'cost_coins' => 300,
                'stock' => 50,
                'quota_per_user' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $reward = Reward::query()->where('code', 'MUG-02')->firstOrFail();

        $this->assertSame(300, $reward->cost_coins);
        $this->assertSame(50, $reward->stock);
        $this->assertTrue($reward->is_active);
    }

    public function test_reward_with_empty_stock_means_unlimited(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/bursa', [
                'code' => 'DIGITAL-01',
                'name' => 'Wallpaper Pack',
                'category' => 'digital',
                'cost_coins' => 50,
                // stock sengaja tidak dikirim: stok tak terbatas.
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull(Reward::query()->where('code', 'DIGITAL-01')->firstOrFail()->stock);
    }

    public function test_deactivating_hides_a_reward_without_deleting_claims(): void
    {
        $reward = Reward::query()->firstOrFail();

        $this->actingAs($this->admin())
            ->post("/admin/bursa/{$reward->id}/nonaktifkan")
            ->assertRedirect();

        $fresh = $reward->fresh();

        $this->assertFalse($fresh->is_active);
        $this->assertNotNull($fresh, 'Hadiah tidak boleh terhapus: klaim lama menunjuknya.');
    }

    public function test_fulfilling_a_claim_marks_it_and_rejects_a_second_time(): void
    {
        $reward = Reward::query()->firstOrFail();
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $claim = RewardRedemption::create([
            'user_id' => $student->id,
            'reward_id' => $reward->id,
            'claim_code' => 'OQ-TEST-0001',
            'coins_spent' => $reward->cost_coins,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/bursa/klaim/{$claim->id}/serahkan")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('fulfilled', $claim->fresh()->status);

        // Menyerahkan dua kali harus ditolak, kalau tidak panitia bisa
        // memberikan barang dua untuk satu klaim.
        $this->actingAs($this->admin())
            ->post("/admin/bursa/klaim/{$claim->id}/serahkan")
            ->assertSessionHasErrors('redemption');
    }

    /* ---------------- 3c: Side Quest wajib ---------------- */

    public function test_certificate_requires_the_required_side_quest(): void
    {
        $service = app(CertificateService::class);

        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $student->forceFill(['orientation_completed_at' => now()])->save();

        // Semua modul utama selesai.
        foreach (Quest::query()->mainline()->get() as $quest) {
            UserQuest::updateOrCreate(
                ['user_id' => $student->id, 'quest_id' => $quest->id],
                [
                    'status' => \App\Enums\QuestStatus::Completed,
                    'progress' => 100,
                    'completed_at' => now(),
                ],
            );
        }

        $this->assertFalse(
            $service->isEligible($student->fresh()),
            'Sertifikat harus tertahan selama misi sosial wajib belum selesai.',
        );

        // Tuntaskan SQ-01.
        $sq = Quest::query()->where('code', 'SQ-01')->firstOrFail();
        UserQuest::updateOrCreate(
            ['user_id' => $student->id, 'quest_id' => $sq->id],
            [
                'status' => \App\Enums\QuestStatus::Completed,
                'progress' => 100,
                'completed_at' => now(),
            ],
        );

        $this->assertTrue(
            $service->isEligible($student->fresh()),
            'Setelah misi sosial selesai, sertifikat harus terbuka.',
        );
    }

    public function test_requiring_a_missing_side_quest_code_does_not_lock_everyone(): void
    {
        config(['ospek.certificate_required_side_quest' => 'SQ-TIDAK-ADA']);

        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->assertTrue(
            app(CertificateService::class)->hasRequiredSideQuest($student),
            'Kode salah konfigurasi tidak boleh mengunci sertifikat semua orang.',
        );
    }

    public function test_side_quest_requirement_can_be_disabled_by_config(): void
    {
        config(['ospek.certificate_required_side_quest' => null]);

        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->assertTrue(app(CertificateService::class)->hasRequiredSideQuest($student));
        $this->assertNull(app(CertificateService::class)->requiredSideQuestCode());
    }
}