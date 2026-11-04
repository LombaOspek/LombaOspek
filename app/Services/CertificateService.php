<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\User;
use App\Models\UserQuest;

/**
 * Kelayakan sertifikat. Dulu halaman /sijil selalu bisa dicetak walau
 * progres 0/7, sehingga sertifikat kehilangan makna sebagai bukti kelulusan.
 */
class CertificateService
{
    public function isEligible(User $user): bool
    {
        return $user->orientation_completed_at !== null
            && $this->completedQuests($user) >= $this->totalQuests()
            && $this->hasRequiredSideQuest($user);
    }

    /**
     * Apakah misi sosial wajib sudah dituntaskan.
     *
     * Side Quest tetap opsional - tidak masuk rantai prasyarat dan tidak
     * menambah node peta - tetapi SATU misi paling ringan dijadikan syarat
     * sertifikat. Tanpa syarat ini, side quest murni opsional akan diabaikan
     * karena sertifikat hanya butuh modul utama, dan tujuan proposal
     * (mendorong interaksi sosial) tidak tercapai.
     *
     * Kode misinya dibaca dari config agar bisa dimatikan tanpa mengubah kode.
     */
    public function hasRequiredSideQuest(User $user): bool
    {
        $code = config('ospek.certificate_required_side_quest');

        if (blank($code)) {
            return true;
        }

        $quest = Quest::query()->where('code', $code)->first();

        // Kode dikonfigurasi tetapi misinya tidak ada (mis. seeder belum
        // dijalankan): jangan mengunci sertifikat semua orang karena salah
        // konfigurasi.
        if ($quest === null) {
            return true;
        }

        return UserQuest::query()
            ->where('user_id', $user->getKey())
            ->where('quest_id', $quest->getKey())
            ->whereNotNull('completed_at')
            ->exists();
    }

    /** Kode misi wajib, untuk ditampilkan di UI. Null bila tidak diwajibkan. */
    public function requiredSideQuestCode(): ?string
    {
        return config('ospek.certificate_required_side_quest') ?: null;
    }

    public function completedQuests(User $user): int
    {
        return app(ProgressionService::class)->completedCount($user);
    }

    public function totalQuests(): int
    {
        // Sertifikat bergantung pada 13 modul utama, bukan misi sosial opsional.
        return (int) Quest::query()->mainline()->count();
    }

    /**
     * @return array{eligible: bool, completed: int, total: int, percentage: int, missing: int, code: string}
     */
    public function status(User $user): array
    {
        $completed = $this->completedQuests($user);
        $total = $this->totalQuests();

        return [
            'eligible' => $this->isEligible($user),
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'missing' => max(0, $total - $completed),
            'code' => $user->certificate_code,
            // Syarat sosial ditampilkan terpisah dari modul: mahasiswa perlu
            // tahu bahwa sertifikatnya tertahan oleh satu misi sosial, bukan
            // oleh modul yang menurutnya sudah selesai semua.
            'side_quest_required' => $this->requiredSideQuestCode(),
            'side_quest_done' => $this->hasRequiredSideQuest($user),
        ];
    }

    public function verify(string $code): ?User
    {
        $normalized = strtoupper(trim($code));

        if ($normalized === '') {
            return null;
        }

        return User::query()
            ->whereNotNull('orientation_completed_at')
            ->get()
            ->first(fn (User $user): bool => $user->certificate_code === $normalized);
    }
}
