<?php

namespace App\Models;

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
        'xp_reward',
        'total_steps',
        'badge_name',
        'badge_color',
    ];

    protected function casts(): array
    {
        return [
            'xp_reward' => 'integer',
            'total_steps' => 'integer',
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
