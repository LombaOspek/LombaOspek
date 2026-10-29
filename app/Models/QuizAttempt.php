<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'module_code',
        'score',
        'total_questions',
        'xp_earned',
        'time_taken_seconds',
        'answers',
        'is_passed',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'total_questions' => 'integer',
            'xp_earned' => 'integer',
            'time_taken_seconds' => 'integer',
            'answers' => 'array',
            'is_passed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
