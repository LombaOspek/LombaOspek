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
}
