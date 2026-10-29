<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\User;

/**
 * Kelayakan sertifikat. Dulu halaman /sijil selalu bisa dicetak walau
 * progres 0/7, sehingga sertifikat kehilangan makna sebagai bukti kelulusan.
 */
class CertificateService
{
    public function isEligible(User $user): bool
    {
        return $user->orientation_completed_at !== null
            && $this->completedQuests($user) >= $this->totalQuests();
    }

    public function completedQuests(User $user): int
    {
        return app(ProgressionService::class)->completedCount($user);
    }

    public function totalQuests(): int
    {
        return (int) Quest::query()->count();
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
