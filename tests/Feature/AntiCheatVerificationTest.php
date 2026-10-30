<?php

namespace Tests\Feature;

use App\Enums\QuestStatus;
use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Enums\VerificationMethod;
use App\Models\Quest;
use App\Models\QuestSubmission;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CompletesQuests;
use Tests\TestCase;

class AntiCheatVerificationTest extends TestCase
{
    use CompletesQuests, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['ospek.orientation.start_date' => now()->subDays(30)->toDateString()]);
        Storage::fake('local');
    }

    private function student(): User
    {
        return User::query()->where('nim', '202653033')->firstOrFail();
    }

    private function officer(?int $group_id = null): User
    {
        $query = User::query()->where('role', UserRole::LiaisonOfficer);

        if ($group_id !== null) {
            $query->where('group_id', $group_id);
        }

        return $query->firstOrFail();
    }

    private function quest(VerificationMethod $method): Quest
    {
        return Quest::query()->where('verification_method', $method)->ordered()->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Token
    |--------------------------------------------------------------------------
    */

    public function test_valid_token_completes_the_quest_and_awards_xp(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Token);
        $this->advanceTo($student, $quest->step_number);

        $quest->setPlainToken('MCC-TEST-01');
        $quest->forceFill(['token_quota' => null])->save();

        $before = $student->refresh()->xp;

        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/token", ['token' => 'MCC-TEST-01'])
            ->assertOk()
            ->assertJson(['success' => true, 'xp_awarded' => $quest->xp_reward]);

        $this->assertSame($before + $quest->xp_reward, $student->refresh()->xp);
        $this->assertSame(
            QuestStatus::Completed,
            app(ProgressionService::class)->statusFor($student, $quest),
        );
    }

    public function test_wrong_token_is_rejected_and_awards_nothing(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Token);
        $this->advanceTo($student, $quest->step_number);

        $quest->setPlainToken('MCC-TEST-01');
        $quest->save();

        $before = $student->refresh()->xp;

        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/token", ['token' => 'SALAH-999'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame($before, $student->refresh()->xp);
    }

    public function test_token_can_only_be_redeemed_once_per_account(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Token);
        $this->advanceTo($student, $quest->step_number);

        $quest->setPlainToken('MCC-TEST-01');
        $quest->forceFill(['token_quota' => null])->save();

        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/token", ['token' => 'MCC-TEST-01'])
            ->assertOk();

        $afterFirst = $student->refresh()->xp;

        // Percobaan kedua dengan token yang sama harus ditolak mentah-mentah.
        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/token", ['token' => 'MCC-TEST-01'])
            ->assertStatus(422);

        $this->assertSame($afterFirst, $student->refresh()->xp);
        $this->assertSame(1, $quest->redemptions()->where('user_id', $student->id)->count());
    }

    public function test_token_quota_is_enforced(): void
    {
        $quest = $this->quest(VerificationMethod::Token);
        $quest->setPlainToken('QUOTA-TEST');
        $quest->forceFill(['token_quota' => 1])->save();

        $first = User::query()->where('nim', '202653044')->firstOrFail();
        $second = User::query()->where('nim', '202653067')->firstOrFail();

        config(['ospek.orientation.start_date' => now()->subDays(30)->toDateString()]);

        $this->advanceTo($first, $quest->step_number);
        $this->advanceTo($second, $quest->step_number);

        app(VerificationService::class)->redeemToken($first, $quest->refresh(), 'QUOTA-TEST');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(VerificationService::class)->redeemToken($second, $quest->refresh(), 'QUOTA-TEST');
    }

    public function test_expired_token_is_rejected(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Token);
        $this->advanceTo($student, $quest->step_number);

        $quest->setPlainToken('EXPIRED-01');
        $quest->forceFill(['token_expires_at' => now()->subDay()])->save();

        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/token", ['token' => 'EXPIRED-01'])
            ->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | GPS
    |--------------------------------------------------------------------------
    */

    public function test_location_inside_radius_completes_quest(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Gps);
        $this->advanceTo($student, $quest->step_number);

        $before = $student->refresh()->xp;

        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/lokasi", [
                'latitude' => (float) $quest->latitude,
                'longitude' => (float) $quest->longitude,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($before + $quest->xp_reward, $student->refresh()->xp);
        $this->assertDatabaseHas('quest_submissions', [
            'user_id' => $student->id,
            'quest_id' => $quest->id,
            'status' => 'approved',
            'distance_meters' => 0,
        ]);
    }

    public function test_location_outside_radius_is_rejected(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Gps);
        $this->advanceTo($student, $quest->step_number);

        $before = $student->refresh()->xp;

        // ~1.1 km di utara auditorium: di luar radius 150 m.
        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/lokasi", [
                'latitude' => (float) $quest->latitude + 0.01,
                'longitude' => (float) $quest->longitude,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame($before, $student->refresh()->xp);
        $this->assertNotSame(
            QuestStatus::Completed,
            app(ProgressionService::class)->statusFor($student, $quest),
        );
    }

    public function test_poor_gps_accuracy_is_rejected(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Gps);
        $this->advanceTo($student, $quest->step_number);

        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/lokasi", [
                'latitude' => (float) $quest->latitude,
                'longitude' => (float) $quest->longitude,
                'accuracy' => 1200,
            ])
            ->assertStatus(422);
    }

    public function test_haversine_distance_is_accurate(): void
    {
        $service = app(VerificationService::class);

        // Jarak antara dua titik yang diketahui ≈ 111.2 km per derajat lintang.
        $this->assertEqualsWithDelta(111_195, $service->haversineMeters(-6.8048, 110.8405, -5.8048, 110.8405), 500);
        $this->assertSame(0, $service->haversineMeters(-6.8048, 110.8405, -6.8048, 110.8405));
    }

    /*
    |--------------------------------------------------------------------------
    | Bukti foto + peninjauan LO
    |--------------------------------------------------------------------------
    */

    public function test_photo_proof_does_not_award_xp_until_approved(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Photo);

        $before = $student->refresh()->xp;

        $this->actingAs($student)
            ->post("/verifikasi-misi/{$quest->id}/foto", [
                'proof' => UploadedFile::fake()->image('bukti.png', 900, 700),
                'note' => 'Tangkapan layar SIAKAD dengan NIM terlihat.',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['success' => true]);

        // Belum ada XP sebelum LO menyetujui.
        $this->assertSame($before, $student->refresh()->xp);
        $this->assertDatabaseHas('quest_submissions', [
            'user_id' => $student->id,
            'quest_id' => $quest->id,
            'status' => 'pending',
        ]);
    }

    public function test_officer_approval_awards_xp_and_completes_quest(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Photo);

        $this->actingAs($student)->post("/verifikasi-misi/{$quest->id}/foto", [
            'proof' => UploadedFile::fake()->image('bukti.png', 900, 700),
        ], ['Accept' => 'application/json'])->assertOk();

        $submission = QuestSubmission::query()->where('user_id', $student->id)->firstOrFail();
        $officer = $this->officer($student->group_id);
        $before = $student->refresh()->xp;

        $this->actingAs($officer)
            ->post("/lo/bukti/{$submission->id}/luluskan")
            ->assertRedirect();

        // XP bertambah minimal sebesar hadiah modul. Bisa lebih karena lencana
        // otomatis ikut terbuka saat modul pertama selesai.
        $after = $student->refresh()->xp;
        $this->assertGreaterThanOrEqual($before + $quest->xp_reward, $after);

        $this->assertSame(
            $quest->xp_reward,
            (int) $student->xpTransactions()->where('source_type', 'quest')->where('source_id', $quest->id)->sum('amount'),
        );

        $this->assertSame(SubmissionStatus::Approved, $submission->refresh()->status);
        $this->assertSame($officer->id, $submission->reviewed_by);
    }

    public function test_officer_rejection_requires_reason_and_awards_nothing(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Photo);

        $this->actingAs($student)->post("/verifikasi-misi/{$quest->id}/foto", [
            'proof' => UploadedFile::fake()->image('bukti.png', 900, 700),
        ], ['Accept' => 'application/json'])->assertOk();

        $submission = QuestSubmission::query()->where('user_id', $student->id)->firstOrFail();
        $officer = $this->officer($student->group_id);
        $before = $student->refresh()->xp;

        // Tanpa alasan → ditolak validasi.
        $this->actingAs($officer)
            ->post("/lo/bukti/{$submission->id}/tolak", [])
            ->assertSessionHasErrors('reason');

        // Dengan alasan → status rejected, XP tetap nol.
        $this->actingAs($officer)
            ->post("/lo/bukti/{$submission->id}/tolak", ['reason' => 'Gambar buram, NIM tidak terbaca.'])
            ->assertRedirect();

        $this->assertSame(SubmissionStatus::Rejected, $submission->refresh()->status);
        $this->assertSame($before, $student->refresh()->xp);
    }

    public function test_officer_cannot_review_students_from_another_group(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Photo);

        $this->actingAs($student)->post("/verifikasi-misi/{$quest->id}/foto", [
            'proof' => UploadedFile::fake()->image('bukti.png', 900, 700),
        ], ['Accept' => 'application/json'])->assertOk();

        $submission = QuestSubmission::query()->where('user_id', $student->id)->firstOrFail();

        // LO dari gugus lain tidak boleh meluluskan.
        $foreignOfficer = User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->where('group_id', '!=', $student->group_id)
            ->firstOrFail();

        $this->actingAs($foreignOfficer)
            ->post("/lo/bukti/{$submission->id}/luluskan")
            ->assertSessionHasErrors('review');

        $this->assertSame(SubmissionStatus::Pending, $submission->refresh()->status);
    }

    public function test_proof_file_is_served_privately_to_authorized_officer_only(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Photo);

        $this->actingAs($student)->post("/verifikasi-misi/{$quest->id}/foto", [
            'proof' => UploadedFile::fake()->image('bukti.png', 900, 700),
        ], ['Accept' => 'application/json'])->assertOk();

        $submission = QuestSubmission::query()->where('user_id', $student->id)->firstOrFail();
        $officer = $this->officer($student->group_id);

        $this->actingAs($officer)->get("/lo/bukti/{$submission->id}")->assertOk();

        $foreignOfficer = User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->where('group_id', '!=', $student->group_id)
            ->firstOrFail();

        $this->actingAs($foreignOfficer)->get("/lo/bukti/{$submission->id}")->assertForbidden();
    }

    public function test_photo_upload_rejects_non_image_files(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Photo);

        $this->actingAs($student)->post("/verifikasi-misi/{$quest->id}/foto", [
            'proof' => UploadedFile::fake()->create('script.php', 100, 'application/x-php'),
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_resubmission_after_rejection_is_allowed(): void
    {
        $student = $this->student();
        $quest = $this->quest(VerificationMethod::Photo);

        $this->actingAs($student)->post("/verifikasi-misi/{$quest->id}/foto", [
            'proof' => UploadedFile::fake()->image('pertama.png'),
        ], ['Accept' => 'application/json'])->assertOk();

        $submission = QuestSubmission::query()->where('user_id', $student->id)->firstOrFail();
        $officer = $this->officer($student->group_id);

        $this->actingAs($officer)->post("/lo/bukti/{$submission->id}/tolak", [
            'reason' => 'Gambar terlalu kecil, mohon ulangi.',
        ]);

        // Unggah ulang harus membuka status pending lagi.
        $this->actingAs($student)->post("/verifikasi-misi/{$quest->id}/foto", [
            'proof' => UploadedFile::fake()->image('kedua.png', 1200, 900),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame(SubmissionStatus::Pending, $submission->refresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Pagar umum
    |--------------------------------------------------------------------------
    */

    public function test_verification_is_rejected_for_locked_quest(): void
    {
        $student = $this->student();
        $quest = Quest::query()->where('step_number', 8)->firstOrFail();

        $this->actingAs($student)
            ->postJson("/verifikasi-misi/{$quest->id}/lokasi", [
                'latitude' => -6.8048,
                'longitude' => 110.8405,
            ])
            ->assertStatus(422);

        $this->assertSame(0, $student->refresh()->xp);
    }

    public function test_guest_cannot_call_verification_endpoints(): void
    {
        $quest = $this->quest(VerificationMethod::Token);

        $this->postJson("/verifikasi-misi/{$quest->id}/token", ['token' => 'APA-SAJA'])
            ->assertUnauthorized();
    }
}