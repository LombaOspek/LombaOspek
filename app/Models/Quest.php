<?php

namespace App\Models;

use App\Enums\QuestActionType;
use App\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Quest extends Model
{
    protected $fillable = [
        'code',
        'step_number',
        'unlock_day',
        'title',
        'category',
        'description',
        'objective',
        'location',
        'xp_reward',
        'estimated_minutes',
        'icon',
        'deadline',
        'action_type',
        'verification_method',
        'status',
        'difficulty',
        'target_scope',
        'target_study_programs',
        'sponsor_name',
        'bonus_reward',
        'token_hash',
        'token_hint',
        'token_quota',
        'token_expires_at',
        'latitude',
        'longitude',
        'radius_meters',
        'quiz_module_code',
        'is_default_completed',
    ];

    protected function casts(): array
    {
        return [
            'step_number' => 'integer',
            'unlock_day' => 'integer',
            'xp_reward' => 'integer',
            'estimated_minutes' => 'integer',
            'difficulty' => 'integer',
            'radius_meters' => 'integer',
            'token_quota' => 'integer',
            'token_expires_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'target_study_programs' => 'array',
            'is_default_completed' => 'boolean',
            'action_type' => QuestActionType::class,
            'verification_method' => VerificationMethod::class,
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('step_number');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_quests')
            ->withPivot(['status', 'completed_at', 'xp_awarded'])
            ->withTimestamps();
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'module_code', 'quiz_module_code');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(QuestSubmission::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(TokenRedemption::class);
    }

    /**
     * Gerbang waktu: modul hanya bisa dikerjakan mulai hari orientasi tertentu.
     */
    public function isUnlockedOnDay(int $day): bool
    {
        return $this->unlock_day <= $day;
    }

    /**
     * Apakah modul ini ditargetkan untuk mahasiswa tertentu berdasarkan
     * program studi (filter multi-tenant pada Quest Builder).
     */
    public function targetsStudent(User $user): bool
    {
        if ($this->target_scope === 'all' || $this->target_scope === null) {
            return true;
        }

        if ($this->target_study_programs === null || $this->target_study_programs === []) {
            return true;
        }

        return in_array($user->major, $this->target_study_programs, true);
    }

    /**
     * Wajib bukti yang tidak bisa diakali? Dipakai UI untuk menandai misi
     * "Anti-Joki" dan memaksa modal verifikasi dibuka.
     */
    public function requiresVerification(): bool
    {
        return $this->verification_method !== VerificationMethod::None;
    }

    public function hasToken(): bool
    {
        return $this->verification_method === VerificationMethod::Token && $this->token_hash !== null;
    }

    public function hasGeofence(): bool
    {
        return $this->verification_method === VerificationMethod::Gps
            && $this->latitude !== null
            && $this->longitude !== null;
    }

    /**
     * Simpan token sebagai hash, bukan plaintext — supaya kebocoran database
     * tidak langsung membocorkan kode stan yang masih berlaku.
     */
    public function setPlainToken(string $token): void
    {
        $normalized = strtoupper(trim($token));

        $this->token_hash = Hash::make($normalized);
        $this->token_hint = substr($normalized, 0, 3).'•••';
    }

    public function tokenMatches(string $token): bool
    {
        if ($this->token_hash === null) {
            return false;
        }

        return Hash::check(strtoupper(trim($token)), $this->token_hash);
    }

    public function tokenQuotaLeft(): ?int
    {
        if ($this->token_quota === null) {
            return null;
        }

        return max(0, $this->token_quota - $this->redemptions()->count());
    }

    public function tokenExpired(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }

    /**
     * Sisa kuota dihitung dari jumlah klaim, bukan dari penghitung terpisah
     * yang bisa melenceng.
     */
    public function tokenQuotaExhausted(): bool
    {
        $left = $this->tokenQuotaLeft();

        return $left !== null && $left <= 0;
    }
}