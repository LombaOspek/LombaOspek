<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'nim',
    'email',
    'password',
    'faculty',
    'major',
    'group_name',
    'mentor_name',
    'phone',
    'xp',
    'level',
    'streak_days',
    'avatar',
    'sound_effects',
    'email_notifications',
    'whatsapp_notifications',
    'dark_mode',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp' => 'integer',
            'level' => 'integer',
            'streak_days' => 'integer',
            'sound_effects' => 'boolean',
            'email_notifications' => 'boolean',
            'whatsapp_notifications' => 'boolean',
            'dark_mode' => 'boolean',
        ];
    }

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }

    public function quests(): BelongsToMany
    {
        return $this->belongsToMany(Quest::class, 'user_quests')
            ->withPivot(['status', 'completed_at'])
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

    public function addXp(int $amount): void
    {
        $this->xp += $amount;
        $newLevel = (int) floor($this->xp / 350) + 1;
        if ($newLevel > $this->level) {
            $this->level = $newLevel;
        }
        $this->save();
    }

    public function getRankTitleAttribute(): string
    {
        if ($this->level >= 6) {
            return 'Mahasiswa Legenda';
        }
        if ($this->level >= 4) {
            return 'Perintis Kampus';
        }
        if ($this->level >= 2) {
            return 'Pengembara Siswa';
        }

        return 'Maba Pemula';
    }
}
