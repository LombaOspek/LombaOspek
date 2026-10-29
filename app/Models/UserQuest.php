<?php

namespace App\Models;

use App\Enums\QuestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuest extends Model
{
    protected $fillable = [
        'user_id',
        'quest_id',
        'status',
        'xp_awarded',
        'completed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuestStatus::class,
            'xp_awarded' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }
}
