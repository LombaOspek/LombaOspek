<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'nim',
    'email',
    'password',
    'role',
    'faculty',
    'major',
    'group_id',
    'group_name',
    'mentor_name',
    'phone',
    'xp',
    'level',
    'streak_days',
    'last_active_on',
    'last_login_at',
    'avatar',
    'avatar_path',
    'sound_effects',
    'email_notifications',
    'whatsapp_notifications',
    'dark_mode',
    'orientation_completed_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'xp' => 'integer',
            'level' => 'integer',
            'streak_days' => 'integer',
            'last_active_on' => 'date',
            'last_login_at' => 'datetime',
            'sound_effects' => 'boolean',
            'email_notifications' => 'boolean',
            'whatsapp_notifications' => 'boolean',
            'dark_mode' => 'boolean',
            'orientation_completed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }

    public function quests(): BelongsToMany
    {
        return $this->belongsToMany(Quest::class, 'user_quests')
            ->withPivot(['status', 'completed_at', 'xp_awarded'])
            ->withTimestamps();
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['current_steps', 'is_unlocked', 'unlocked_at'])
            ->withTimestamps();
    }

    public function helpTickets(): HasMany
    {
        return $this->hasMany(HelpTicket::class);
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(QuestSubmission::class);
    }

    /**
     * Gugus-gugus yang dibimbing user ini bila ia seorang LO.
     */
    public function mentoredGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'mentor_id');
    }

    public function isMentor(): bool
    {
        return $this->role->canReviewTickets();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isLiaisonOfficer(): bool
    {
        return $this->role === UserRole::LiaisonOfficer;
    }

    /**
     * Boleh meninjau bukti milik mahasiswa tertentu?
     *
     * LO hanya boleh meninjau anggota gugusnya sendiri; admin boleh semuanya.
     * Tanpa batas ini seorang LO bisa meluluskan mahasiswa gugus lain.
     */
    public function canReviewSubmissionOf(User $student): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isLiaisonOfficer()) {
            return false;
        }

        if ($this->group_id === null || $student->group_id === null) {
            return false;
        }

        return $this->group_id === $student->group_id;
    }

    /**
     * Judul level berdasarkan pita XP pada config/ospek.php.
     */
    public function getLevelTitleAttribute(): string
    {
        /** @var array<int, array{title: string, min_xp: int}> $levels */
        $levels = config('ospek.levels', []);

        return $levels[$this->level]['title'] ?? 'Maba Pemula';
    }

    public function getNextLevelXpAttribute(): ?int
    {
        /** @var array<int, array{title: string, min_xp: int}> $levels */
        $levels = config('ospek.levels', []);

        return $levels[$this->level + 1]['min_xp'] ?? null;
    }

    public function getLevelProgressAttribute(): int
    {
        /** @var array<int, array{title: string, min_xp: int}> $levels */
        $levels = config('ospek.levels', []);

        $currentFloor = $levels[$this->level]['min_xp'] ?? 0;
        $nextFloor = $levels[$this->level + 1]['min_xp'] ?? null;

        if ($nextFloor === null) {
            return 100;
        }

        $span = max(1, $nextFloor - $currentFloor);

        return (int) min(100, max(0, round((($this->xp - $currentFloor) / $span) * 100)));
    }

    public function getXpToNextLevelAttribute(): ?int
    {
        return $this->next_level_xp === null
            ? null
            : max(0, $this->next_level_xp - $this->xp);
    }

    /**
     * Kode verifikasi sertifikat: stabil per mahasiswa, ditandatangani APP_KEY.
     */
    public function getCertificateCodeAttribute(): string
    {
        $hash = strtoupper(substr(hash_hmac('sha256', "cert:{$this->id}", (string) config('app.key')), 0, 8));

        return 'OSQ-'.now()->format('y').'-'.$hash;
    }
}