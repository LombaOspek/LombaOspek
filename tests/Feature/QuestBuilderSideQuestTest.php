<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quest Builder: Side Quest + dropdown custom.
 *
 * Dua keluhan yang diperbaiki di sini:
 *   1. Admin hanya bisa membuat modul peta; tidak ada cara membuat Side Quest
 *      dari UI, padahal Side Quest punya seeder sendiri.
 *   2. Dropdown di Quest Builder masih `<select>` bawaan, berbeda dari sisa
 *      aplikasi yang memakai komponen custom.
 */
class QuestBuilderSideQuestTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Misi Sosial Uji Coba',
            'category' => 'Sosial',
            'description' => 'Deskripsi misi sosial yang cukup panjang untuk lolos validasi minimum.',
            'objective' => 'Menguji pembuatan Side Quest dari panel admin.',
            'location' => 'Lingkungan Kampus',
            'xp_reward' => 100,
            'estimated_minutes' => 15,
            'unlock_day' => 3,
            'action_type' => 'reflection',
            'verification_method' => 'none',
            'status' => 'published',
            'difficulty' => 1,
            'target_scope' => 'all',
        ], $overrides);
    }

    public function test_the_builder_page_offers_a_side_quest_option(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/misi')->assertOk()->getContent();

        $this->assertStringContainsString('data-quest-kind', $html, 'Toggle jenis misi tidak ada.');
        $this->assertStringContainsString('Side Quest', $html);
        $this->assertStringContainsString('data-side-quest-only', $html, 'Blok syarat Side Quest tidak ada.');
    }

    public function test_an_admin_can_create_a_side_quest(): void
    {
        $this->actingAs($this->admin())->post('/admin/misi', $this->payload([
            'is_side_quest' => 1,
            'required_completed_modules' => 3,
        ]))->assertRedirect();

        $quest = Quest::query()->where('title', 'Misi Sosial Uji Coba')->first();

        $this->assertNotNull($quest, 'Side Quest tidak tersimpan.');
        $this->assertTrue((bool) $quest->is_side_quest);
        $this->assertSame(3, (int) $quest->required_completed_modules);
    }

    public function test_a_created_side_quest_gets_an_sq_code_not_mod(): void
    {
        /*
         * Kode MOD- terikat pada `step_number` yang juga urutan peta. Side Quest
         * tidak berada di peta, jadi memakai MOD- akan menabrak kode modul
         * utama dan merusak penomoran.
         */
        $this->actingAs($this->admin())->post('/admin/misi', $this->payload([
            'is_side_quest' => 1,
            'required_completed_modules' => 0,
        ]))->assertRedirect();

        $quest = Quest::query()->where('title', 'Misi Sosial Uji Coba')->firstOrFail();

        $this->assertMatchesRegularExpression('/^SQ-\d{2}$/', $quest->code);
        $this->assertSame(0, (int) $quest->step_number, 'Side Quest tidak menempati urutan peta.');
    }

    public function test_a_side_quest_does_not_need_a_step_number(): void
    {
        $this->actingAs($this->admin())->post('/admin/misi', $this->payload([
            'is_side_quest' => 1,
            'required_completed_modules' => 0,
            // step_number sengaja tidak dikirim
        ]))->assertSessionHasNoErrors();

        $this->assertNotNull(Quest::query()->where('title', 'Misi Sosial Uji Coba')->first());
    }

    public function test_a_mainline_quest_still_requires_a_step_number(): void
    {
        $this->actingAs($this->admin())->post('/admin/misi', $this->payload([
            'is_side_quest' => 0,
        ]))->assertSessionHasErrors('step_number');
    }

    public function test_two_side_quests_get_different_codes(): void
    {
        $this->actingAs($this->admin())->post('/admin/misi', $this->payload([
            'title' => 'Side Quest Pertama',
            'is_side_quest' => 1,
        ]))->assertRedirect();

        $this->actingAs($this->admin())->post('/admin/misi', $this->payload([
            'title' => 'Side Quest Kedua',
            'is_side_quest' => 1,
        ]))->assertRedirect();

        $codes = Quest::query()
            ->whereIn('title', ['Side Quest Pertama', 'Side Quest Kedua'])
            ->pluck('code');

        $this->assertCount(2, $codes);
        $this->assertSame($codes->unique()->count(), $codes->count(), 'Kode Side Quest bertabrakan.');
    }

    public function test_the_builder_uses_custom_dropdowns_not_native_selects(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/misi')->assertOk()->getContent();

        $this->assertStringNotContainsString('<select', $html, 'Masih ada <select> bawaan di Quest Builder.');
        $this->assertStringContainsString('data-dropdown', $html, 'Komponen dropdown custom tidak dipakai.');
    }

    public function test_no_native_select_remains_anywhere_in_the_admin_pages(): void
    {
        /*
         * Keluhan yang lebih luas: "semua dropdown pada project ini harus
         * menggunakan custom dropdown". Dikunci di seluruh halaman yang
         * dijangkau admin supaya tidak ada satu pun yang tertinggal.
         */
        $admin = $this->admin();

        foreach (['/admin/akun', '/admin/bursa', '/admin/misi', '/profil'] as $path) {
            $html = $this->actingAs($admin)->get($path)->assertOk()->getContent();

            $this->assertStringNotContainsString(
                '<select',
                $html,
                "Masih ada <select> bawaan di {$path}.",
            );
        }
    }
}