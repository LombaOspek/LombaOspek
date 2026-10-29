<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    protected $fillable = [
        'code',
        'step_number',
        'title',
        'category',
        'description',
        'location',
        'xp_reward',
        'icon',
        'deadline',
        'action_type',
        'is_default_completed',
    ];

    protected function casts(): array
    {
        return [
            'step_number' => 'integer',
            'xp_reward' => 'integer',
            'is_default_completed' => 'boolean',
        ];
    }

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_quests')
            ->withPivot(['status', 'completed_at'])
            ->withTimestamps();
    }
}
