<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'module_code',
        'module_title',
        'question_number',
        'category',
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'hint',
        'explanation',
        'xp_reward',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'xp_reward' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getOptionsAttribute(): array
    {
        return [
            'A' => $this->option_a,
            'B' => $this->option_b,
            'C' => $this->option_c,
            'D' => $this->option_d,
        ];
    }

    public function isCorrect(?string $option): bool
    {
        return $option !== null && strtoupper($option) === strtoupper($this->correct_option);
    }
}
