<?php

namespace App\Models;

use App\Enums\QuestActionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'is_default_completed' => 'boolean',
            'action_type' => QuestActionType::class,
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('step_number');
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

    /**
     * Gerbang waktu: modul hanya bisa dikerjakan mulai hari orientasi tertentu.
     */
    public function isUnlockedOnDay(int $day): bool
    {
        return $this->unlock_day <= $day;
    }
}
