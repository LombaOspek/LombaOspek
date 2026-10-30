<?php

namespace Tests\Feature;

use App\Enums\QuestActionType;
use App\Enums\UserRole;
use App\Enums\VerificationMethod;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quest Builder: admin merancang misi baru beserta mekanisme verifikasinya.
 */
class QuestBuilderTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Misi Uji Coba Baru',
            'category' => 'Eksplorasi Kampus',
            'description' => 'Deskripsi misi uji coba yang cukup panjang untuk lolos validasi minimum.',
            'xp_reward' => 200,
            'estimated_minutes' => 30,
            'step_number' => 20,
            'unlock_day' => 1,
            'action_type' => QuestActionType::Reflection->value,
            'verification_method' => VerificationMethod::None->value,
            'status' => 'published',
            'difficulty' => 2,
            'target_scope' => 'all',
        ], $overrides);
    }

    public function test_admin_can_publish_a_quest(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload())
            ->assertRedirect(route('admin.quests.index'))
            ->assertSessionHasNoErrors();

        $quest = Quest::query()->where('title', 'Misi Uji Coba Baru')->firstOrFail();

        $this->assertSame('MOD-20', $quest->code);
        $this->assertSame('published', $quest->status);
        $this->assertSame(VerificationMethod::None, $quest->verification_method);
        $this->assertSame(200, $quest->xp_reward);
    }

    public function test_admin_can_save_a_draft(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload(['status' => 'draft', 'step_number' => 21]))
            ->assertRedirect();

        $this->assertDatabaseHas('quests', [
            'title' => 'Misi Uji Coba Baru',
            'status' => 'draft',
        ]);
    }

    public function test_token_quest_requires_a_code_and_stores_only_a_hash(): void
    {
        // Tanpa kode token → ditolak.
        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload([
                'verification_method' => VerificationMethod::Token->value,
                'step_number' => 22,
            ]))
            ->assertSessionHasErrors('token_code');

        // Dengan kode token → tersimpan sebagai hash, plaintext tidak masuk DB.
        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload([
                'verification_method' => VerificationMethod::Token->value,
                'token_code' => 'STAN-RAHASIA-01',
                'step_number' => 23,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $quest = Quest::query()->where('step_number', 23)->firstOrFail();

        $this->assertNotNull($quest->token_hash);
        $this->assertStringNotContainsString('STAN-RAHASIA-01', (string) $quest->token_hash);
        $this->assertTrue($quest->tokenMatches('STAN-RAHASIA-01'));
        $this->assertFalse($quest->tokenMatches('SALAH'));
    }

    public function test_gps_quest_requires_coordinates(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload([
                'verification_method' => VerificationMethod::Gps->value,
                'step_number' => 24,
            ]))
            ->assertSessionHasErrors('latitude');

        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload([
                'verification_method' => VerificationMethod::Gps->value,
                'latitude' => -6.8048,
                'longitude' => 110.8405,
                'radius_meters' => 150,
                'step_number' => 25,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('quests', [
            'step_number' => 25,
            'verification_method' => 'gps',
        ]);
    }

    public function test_quiz_quest_gets_a_padded_module_code(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload([
                'action_type' => QuestActionType::Quiz->value,
                'step_number' => 9,
            ]))
            ->assertRedirect();

        $quest = Quest::query()->where('step_number', 9)->firstOrFail();

        // Harus konsisten dengan kode modul lain: MOD-09, bukan MOD-9.
        $this->assertSame('MOD-09', $quest->code);
        $this->assertSame('MOD-09', $quest->quiz_module_code);
    }

    public function test_duplicate_step_number_is_rejected_instead_of_colliding(): void
    {
        $existing = Quest::query()->firstOrFail();

        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload(['step_number' => $existing->step_number]))
            ->assertSessionHasErrors('step_number');

        // Hanya satu modul dengan urutan tersebut.
        $this->assertSame(1, Quest::query()->where('step_number', $existing->step_number)->count());
    }

    public function test_quest_cannot_be_published_without_a_description(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/misi', $this->payload(['description' => 'pendek']))
            ->assertSessionHasErrors('description');
    }

    public function test_admin_can_update_an_existing_quest(): void
    {
        $quest = Quest::query()->firstOrFail();

        $this->actingAs($this->admin())
            ->put("/admin/misi/{$quest->id}", $this->payload([
                'title' => 'Judul Diperbarui',
                'step_number' => $quest->step_number,
                'xp_reward' => 999,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $quest->refresh();
        $this->assertSame('Judul Diperbarui', $quest->title);
        $this->assertSame(999, $quest->xp_reward);
        // Kode modul tidak boleh berubah saat penyuntingan.
        $this->assertSame('MOD-01', $quest->code);
    }

    public function test_changing_token_keeps_the_old_one_working_until_replaced(): void
    {
        $quest = Quest::query()->where('verification_method', 'token')->firstOrFail();
        $quest->setPlainToken('TOKEN-LAMA-01');
        $quest->save();

        // Simpan perubahan tanpa menyentuh token_code → token lama tetap berlaku.
        $this->actingAs($this->admin())
            ->put("/admin/misi/{$quest->id}", $this->payload([
                'title' => $quest->title,
                'step_number' => $quest->step_number,
                'verification_method' => VerificationMethod::Token->value,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($quest->refresh()->tokenMatches('TOKEN-LAMA-01'));
    }

    public function test_non_admin_cannot_reach_the_builder(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $officer = User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();

        $this->actingAs($student)->get('/admin/misi')->assertForbidden();
        $this->actingAs($officer)->get('/admin/misi')->assertForbidden();

        $this->actingAs($student)->post('/admin/misi', $this->payload())->assertForbidden();
        $this->actingAs($officer)->post('/admin/misi', $this->payload())->assertForbidden();

        $this->assertDatabaseMissing('quests', ['title' => 'Misi Uji Coba Baru']);
    }

    public function test_builder_page_lists_existing_quests(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/misi')
            ->assertOk()
            ->assertSee('MOD-01');
    }
}