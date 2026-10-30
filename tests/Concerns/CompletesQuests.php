<?php

namespace Tests\Concerns;

use App\Enums\QuestActionType;
use App\Enums\QuestStatus;
use App\Enums\VerificationMethod;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\QuizService;
use App\Services\VerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Membantu tes menyelesaikan modul lewat jalur yang SAH sesuai metode
 * verifikasinya. Dipakai agar tes progres tidak "menipu" sistem dengan
 * memanggil endpoint selesai pada modul yang seharusnya butuh bukti.
 */
trait CompletesQuests
{
    /**
     * Selesaikan satu modul dengan cara yang benar.
     */
    protected function completeQuestProperly(User $user, Quest $quest): void
    {
        $user->refresh();
        $verification = app(VerificationService::class);

        if ($quest->action_type === QuestActionType::Quiz) {
            app(QuizService::class)->grade($user, $quest->quiz_module_code, [
                1 => 'B', 2 => 'C', 3 => 'C', 4 => 'B', 5 => 'B',
            ]);

            return;
        }

        switch ($quest->verification_method) {
            case VerificationMethod::Token:
                $this->redeemKnownToken($user, $quest);
                break;

            case VerificationMethod::Gps:
                $verification->verifyLocation($user, $quest, (float) $quest->latitude, (float) $quest->longitude);
                break;

            case VerificationMethod::Photo:
                Storage::fake('local');
                $verification->submitPhotoProof(
                    $user,
                    $quest,
                    UploadedFile::fake()->image('bukti.png', 800, 600),
                    'Bukti untuk pengujian.',
                );
                // Bukti foto butuh persetujuan LO; di tes kita setujui langsung
                // memakai LO gugus yang sama.
                $submission = $verification->submissionFor($user, $quest);
                $officer = $this->officerFor($user);
                $verification->review($officer, $submission, true);
                break;

            default:
                // Jalur non-verifikasi: refleksi wajib bila diminta.
                $payload = ['confirm' => '1'];

                if ($quest->action_type === QuestActionType::Reflection) {
                    $payload['reflection'] = str_repeat('Refleksi yang cukup panjang untuk lolos validasi tes. ', 2);
                }

                $this->actingAs($user)->post("/misi/{$quest->getKey()}/selesai", $payload);
                break;
        }

        $user->refresh();
    }

    /**
     * Token plaintext hanya diketahui seeder. Untuk tes, kita set token baru
     * pada quest lalu menebaknya — meniru panitia yang membagikan kode.
     */
    protected function redeemKnownToken(User $user, Quest $quest): void
    {
        $token = 'TES-TOKEN-26';
        $quest->setPlainToken($token);
        $quest->forceFill(['token_quota' => null, 'token_expires_at' => null])->save();

        app(VerificationService::class)->redeemToken($user, $quest->refresh(), $token);
    }

    /**
     * LO yang berwenang atas mahasiswa ini.
     */
    protected function officerFor(User $user): User
    {
        return User::query()
            ->where('role', \App\Enums\UserRole::LiaisonOfficer)
            ->where('group_id', $user->group_id)
            ->firstOrFail();
    }

    /**
     * Selesaikan seluruh modul yang terbuka berurutan sampai target tertentu.
     */
    protected function advanceTo(User $user, int $upToStep): void
    {
        $progression = app(ProgressionService::class);

        foreach (Quest::query()->ordered()->get() as $quest) {
            if ($quest->step_number >= $upToStep) {
                break;
            }

            if ($progression->statusFor($user, $quest) === QuestStatus::Completed) {
                continue;
            }

            $this->completeQuestProperly($user, $quest);
        }
    }
}