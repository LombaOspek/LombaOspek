<?php

namespace App\Enums;

enum AchievementMetric: string
{
    case QuestsCompleted = 'quests_completed';
    case QuizzesPassed = 'quizzes_passed';
    case PerfectQuizzes = 'perfect_quizzes';
    case StreakDays = 'streak_days';
    case TotalXp = 'total_xp';
    case HelpTicketsAnswered = 'help_tickets_answered';

    public function label(): string
    {
        return match ($this) {
            self::QuestsCompleted => 'Modul diselesaikan',
            self::QuizzesPassed => 'Kuis lulus',
            self::PerfectQuizzes => 'Kuis skor sempurna',
            self::StreakDays => 'Hari rentetan aktif',
            self::TotalXp => 'Total XP',
            self::HelpTicketsAnswered => 'Tiket bantuan terjawab',
        };
    }
}
