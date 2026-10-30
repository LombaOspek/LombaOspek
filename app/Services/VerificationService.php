<?php

namespace App\Services;

use App\Enums\QuestStatus;
use App\Enums\SubmissionStatus;
use App\Enums\VerificationMethod;
use App\Models\Quest;
use App\Models\QuestSubmission;
use App\Models\TokenRedemption;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Mesin verifikasi anti-curang.
 *
 * Prinsipnya: server tidak pernah percaya klaim dari browser. Token dicocokkan
 * terhadap hash, jarak GPS dihitung dari koordinat yang dikirim lalu divalidasi
 * terhadap geofence, dan bukti foto tidak pernah otomatis meloloskan modul —
 * harus ada LO yang meninjau.
 */
class VerificationService
{
    /** Akurasi GPS maksimum yang masih dipercaya (meter). */
    private const MAX_ACCURACY_METERS = 500;

    public function __construct(
        private readonly ProgressionService $progression,
        private readonly XpService $xp,
        private readonly AchievementService $achievements,
    ) {}

    /**
     * Klaim token rahasia stan.
     *
     * @return array{success: bool, message: string, xp_awarded: int, user_xp: int}
     */
    public function redeemToken(User $user, Quest $quest, string $token, ?string $ip = null): array
    {
        $this->assertVerifiable($user, $quest, VerificationMethod::Token);

        if (! $quest->hasToken()) {
            throw ValidationException::withMessages([
                'token' => 'Modul ini belum memiliki token yang sah.',
            ]);
        }

        if ($quest->tokenExpired()) {
            throw ValidationException::withMessages([
                'token' => 'Masa berlaku token sudah berakhir.',
            ]);
        }

        if ($quest->tokenQuotaExhausted()) {
            throw ValidationException::withMessages([
                'token' => 'Kuota token untuk modul ini sudah habis.',
            ]);
        }

        // Sudah pernah klaim? Cek lebih awal agar pesannya jelas.
        $already = TokenRedemption::query()
            ->where('quest_id', $quest->getKey())
            ->where('user_id', $user->getKey())
            ->exists();

        if ($already) {
            throw ValidationException::withMessages([
                'token' => 'Anda sudah pernah mengklaim token modul ini.',
            ]);
        }

        if (! $quest->tokenMatches($token)) {
            throw ValidationException::withMessages([
                'token' => 'Kode token salah. Pastikan sesuai yang diberikan panitia stan.',
            ]);
        }

        return DB::transaction(function () use ($user, $quest, $token, $ip): array {
            // `insertOrIgnore` + unique index = penjaga terakhir bila dua request
            // masuk bersamaan. Kalau baris tidak bertambah, klaim sudah ada.
            $inserted = TokenRedemption::query()->insertOrIgnore([
                'quest_id' => $quest->getKey(),
                'user_id' => $user->getKey(),
                'token_hash' => hash('sha256', strtoupper(trim($token))),
                'ip_address' => $ip,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                throw ValidationException::withMessages([
                    'token' => 'Token ini sudah pernah Anda klaim.',
                ]);
            }

            return $this->finalise(
                $user,
                $quest,
                VerificationMethod::Token,
                ['token_used_hash' => hash('sha256', strtoupper(trim($token)))],
                'Token stan berhasil diverifikasi.',
            );
        });
    }

    /**
     * Verifikasi berbasis koordinat. Jarak dihitung di server memakai formula
     * haversine dari koordinat yang dikirim klien.
     *
     * @return array{success: bool, message: string, xp_awarded: int, user_xp: int, distance: int}
     */
    public function verifyLocation(User $user, Quest $quest, float $lat, float $lng, ?float $accuracy = null): array
    {
        $this->assertVerifiable($user, $quest, VerificationMethod::Gps);

        // Akurasi diperiksa SETELAH gerbang modul supaya mahasiswa tidak
        // menerima pesan GPS untuk modul yang masih terkunci.
        if ($accuracy !== null && $accuracy > self::MAX_ACCURACY_METERS) {
            throw ValidationException::withMessages([
                'location' => sprintf(
                    'Sinyal GPS terlalu lemah (akurasi %d m). Coba di area terbuka.',
                    (int) $accuracy,
                ),
            ]);
        }

        if (! $quest->hasGeofence()) {
            throw ValidationException::withMessages([
                'location' => 'Titik lokasi modul ini belum diatur panitia.',
            ]);
        }

        $distance = $this->haversineMeters($lat, $lng, $quest->latitude, $quest->longitude);

        if ($distance > $quest->radius_meters) {
            throw ValidationException::withMessages([
                'location' => sprintf(
                    'Anda berada %d m dari lokasi. Maksimal %d m.',
                    $distance,
                    $quest->radius_meters,
                ),
            ]);
        }

        $result = $this->finalise(
            $user,
            $quest,
            VerificationMethod::Gps,
            [
                'latitude' => $lat,
                'longitude' => $lng,
                'distance_meters' => $distance,
            ],
            "Lokasi terverifikasi dalam radius {$quest->radius_meters} m.",
        );

        return $result + ['distance' => $distance];
    }

    /**
     * Unggah bukti foto. Status modul TIDAK berubah sampai LO menyetujui,
     * sehingga mahasiswa tidak bisa mendapatkan XP dengan mengunggah gambar acak.
     *
     * @return array{success: bool, message: string, submission_id: int}
     */
    public function submitPhotoProof(User $user, Quest $quest, UploadedFile $proof, ?string $note = null): array
    {
        $this->assertOpen($user, $quest);

        if ($quest->verification_method !== VerificationMethod::Photo) {
            throw ValidationException::withMessages([
                'proof' => 'Modul ini tidak memakai bukti foto.',
            ]);
        }

        $existing = QuestSubmission::query()
            ->where('user_id', $user->getKey())
            ->where('quest_id', $quest->getKey())
            ->first();

        if ($existing?->status === SubmissionStatus::Pending) {
            throw ValidationException::withMessages([
                'proof' => 'Bukti Anda sedang menunggu tinjauan LO.',
            ]);
        }

        if ($existing?->status === SubmissionStatus::Approved) {
            throw ValidationException::withMessages([
                'proof' => 'Modul ini sudah disetujui.',
            ]);
        }

        // Simpan di disk privat: bukti mahasiswa tidak boleh dapat diakses publik.
        $path = $proof->store("proofs/{$user->getKey()}", 'local');

        $submission = QuestSubmission::updateOrCreate(
            ['user_id' => $user->getKey(), 'quest_id' => $quest->getKey()],
            [
                'method' => VerificationMethod::Photo,
                'status' => SubmissionStatus::Pending,
                'proof_path' => $path,
                'note' => $note,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ],
        );

        return [
            'success' => true,
            'message' => 'Bukti terkirim. Menunggu tinjauan LO gugus Anda.',
            'submission_id' => $submission->getKey(),
        ];
    }

    /**
     * Keputusan LO atas bukti foto. XP baru diberikan di sini.
     */
    public function review(User $reviewer, QuestSubmission $submission, bool $approve, ?string $reason = null): array
    {
        if (! $reviewer->role->canVerifySubmissions()) {
            throw ValidationException::withMessages([
                'review' => 'Anda tidak berwenang meninjau bukti misi.',
            ]);
        }

        $student = $submission->user;

        if (! $reviewer->canReviewSubmissionOf($student)) {
            throw ValidationException::withMessages([
                'review' => 'Mahasiswa ini bukan anggota gugus Anda.',
            ]);
        }

        if ($submission->status !== SubmissionStatus::Pending) {
            throw ValidationException::withMessages([
                'review' => 'Bukti ini sudah ditinjau sebelumnya.',
            ]);
        }

        if (! $approve && blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penolakan wajib diisi agar mahasiswa tahu apa yang harus diperbaiki.',
            ]);
        }

        return DB::transaction(function () use ($reviewer, $submission, $approve, $reason): array {
            $submission->forceFill([
                'status' => $approve ? SubmissionStatus::Approved : SubmissionStatus::Rejected,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => $approve ? null : $reason,
            ])->save();

            if (! $approve) {
                return [
                    'success' => true,
                    'approved' => false,
                    'message' => 'Bukti ditolak. Mahasiswa dapat mengunggah perbaikan.',
                    'xp_awarded' => 0,
                ];
            }

            $quest = $submission->quest;
            $result = $this->finalise(
                $submission->user,
                $quest,
                VerificationMethod::Photo,
                [],
                'Bukti foto disetujui oleh '.$reviewer->name.'.',
            );

            return $result + ['approved' => true];
        });
    }

    /**
     * Apakah mahasiswa sudah punya pengajuan aktif untuk modul ini?
     */
    public function submissionFor(User $user, Quest $quest): ?QuestSubmission
    {
        return QuestSubmission::query()
            ->where('user_id', $user->getKey())
            ->where('quest_id', $quest->getKey())
            ->first();
    }

    public function pendingCountFor(User $student): int
    {
        return QuestSubmission::query()
            ->where('user_id', $student->getKey())
            ->pending()
            ->count();
    }

    /**
     * Penyelesaian akhir: tandai modul selesai, beri XP, evaluasi lencana.
     *
     * @param  array<string, mixed>  $evidence
     * @return array{success: bool, message: string, xp_awarded: int, user_xp: int}
     */
    private function finalise(User $user, Quest $quest, VerificationMethod $method, array $evidence, string $message): array
    {
        $this->progression->complete($user, $quest, $message, $quest->xp_reward);

        $this->xp->award(
            $user,
            $quest->xp_reward,
            'quest',
            $quest->getKey(),
            "Modul terverifikasi ({$method->label()}): {$quest->title}",
            "quest:{$user->getKey()}:{$quest->getKey()}",
        );

        // Catat bukti bila verifikasi instan (token / GPS).
        if ($evidence !== []) {
            QuestSubmission::updateOrCreate(
                ['user_id' => $user->getKey(), 'quest_id' => $quest->getKey()],
                [
                    'method' => $method,
                    'status' => SubmissionStatus::Approved,
                    'reviewed_at' => now(),
                    'note' => $message,
                ] + $evidence,
            );
        }

        $this->achievements->sync($user);

        $user->refresh();

        return [
            'success' => true,
            'message' => $message.' +'.$quest->xp_reward.' XP',
            'xp_awarded' => $quest->xp_reward,
            'user_xp' => $user->xp,
        ];
    }

    private function assertOpen(User $user, Quest $quest): void
    {
        $status = $this->progression->statusFor($user, $quest);

        if ($status === QuestStatus::Completed) {
            throw ValidationException::withMessages([
                'quest' => 'Modul ini sudah selesai.',
            ]);
        }

        if (! $status->isOpen()) {
            throw ValidationException::withMessages([
                'quest' => 'Modul ini belum terbuka untuk Anda.',
            ]);
        }
    }

    private function assertVerifiable(User $user, Quest $quest, VerificationMethod $expected): void
    {
        $this->assertOpen($user, $quest);

        if ($quest->verification_method !== $expected) {
            throw ValidationException::withMessages([
                'quest' => 'Metode verifikasi modul ini tidak sesuai.',
            ]);
        }
    }

    /**
     * Jarak antara dua koordinat dalam meter (haversine).
     */
    public function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}