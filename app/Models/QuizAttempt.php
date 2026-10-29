<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'module_code',
        'attempt_number',
        'score',
        'total_questions',
        'xp_earned',
        'time_taken_seconds',
        'expires_at',
        'answers',
        'is_passed',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'score' => 'integer',
            'total_questions' => 'integer',
            'xp_earned' => 'integer',
            'time_taken_seconds' => 'integer',
            'expires_at' => 'datetime',
            'answers' => 'array',
            'is_passed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPercentageAttribute(): int
    {
        return $this->total_questions > 0
            ? (int) round(($this->score / $this->total_questions) * 100)
            : 0;
    }
}
