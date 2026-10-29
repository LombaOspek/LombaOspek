<?php

namespace App\Models;

use App\Enums\AchievementMetric;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'code',
        'title',
        'description',
        'icon',
        'category',
        'metric',
        'xp_reward',
        'total_steps',
        'is_automatic',
        'badge_name',
        'badge_color',
    ];

    protected function casts(): array
    {
        return [
            'metric' => AchievementMetric::class,
            'xp_reward' => 'integer',
            'total_steps' => 'integer',
            'is_automatic' => 'boolean',
        ];
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot(['current_steps', 'is_unlocked', 'unlocked_at'])
            ->withTimestamps();
    }
}
