<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\OrientationSchedule;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Urutan modul harus mengikuti hari.
 *
 * Bug yang diperbaiki: pada Bab 1, `step_number` tidak sejalan dengan
 * `unlock_day` — step 1 hari 1, step 2 hari 2, lalu step 3 **hari 1 lagi**.
 * Di peta misi itu terlihat sebagai lompatan mundur: setelah menyelesaikan
 * tugas hari 2, mahasiswa dihadapkan pada tugas hari 1. Bab 2 dan 3 tidak
 * bermasalah karena urutannya memang rapi.
 */
class QuestDayOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_unlock_days_never_go_backwards_along_the_path(): void
    {
        $days = Quest::query()->mainline()->ordered()->pluck('unlock_day', 'step_number');

        $previous = 0;
        $violations = [];

        foreach ($days as $step => $day) {
            if ($day < $previous) {
                $violations[] = "step {$step} (hari {$day}) mundur dari hari {$previous}";
            }
            $previous = max($previous, $day);
        }

        $this->assertSame(
            [],
            $violations,
            "Urutan hari tidak monoton:\n  ".implode("\n  ", $violations),
        );
    }

    public function test_every_day_used_by_the_catalog_is_reachable(): void
    {
        $total = (int) config('ospek.orientation.days');
        $days = Quest::query()->mainline()->pluck('unlock_day')->unique()->sort()->values();

        $this->assertSame(1, $days->first(), 'Modul pertama harus terbuka hari 1.');
        $this->assertLessThanOrEqual($total, $days->last(), 'Ada modul melewati hari terakhir.');
    }

    public function test_the_first_chapter_is_ordered(): void
    {
        /*
         * Bab 1 spesifik: empat modul pertama harus memakai hari yang tidak
         * menurun. Ini yang dilaporkan rusak, jadi dikunci terpisah supaya
         * tidak bisa kembali tanpa ketahuan.
         */
        $chapter = collect(config('ospek.chapters'))->first();
        $from = (int) $chapter['from'];
        $to = (int) $chapter['to'];

        $days = Quest::query()
            ->mainline()
            ->ordered()
            ->get()
            ->filter(fn (Quest $q) => $q->unlock_day >= $from && $q->unlock_day <= $to)
            ->pluck('unlock_day')
            ->values()
            ->all();

        $sorted = $days;
        sort($sorted);

        $this->assertSame($sorted, $days, 'Modul Bab 1 tidak urut hari: '.implode(',', $days));
    }

    public function test_the_schedule_and_the_catalog_agree_on_days(): void
    {
        /*
         * Jadwal dan peta misi harus menunjuk hari yang sama untuk modul yang
         * sama. Kalau tidak, mahasiswa membaca "Seminar hari 2" di jadwal lalu
         * menemukan kuisnya terbuka di hari 1.
         */
        $mismatch = [];

        foreach (OrientationSchedule::query()->whereNotNull('quest_id')->with('quest')->get() as $agenda) {
            if ($agenda->quest === null) {
                continue;
            }

            if ((int) $agenda->day !== (int) $agenda->quest->unlock_day) {
                $mismatch[] = sprintf(
                    '%s: agenda hari %d, modul hari %d',
                    $agenda->quest->code,
                    $agenda->day,
                    $agenda->quest->unlock_day,
                );
            }
        }

        $this->assertSame([], $mismatch, "Jadwal dan katalog tidak cocok:\n  ".implode("\n  ", $mismatch));
    }

    public function test_every_chapter_leans_on_a_contiguous_day_range(): void
    {
        $chapters = collect(config('ospek.chapters'));

        foreach ($chapters as $chapter) {
            $this->assertLessThanOrEqual(
                $chapter['to'],
                $chapter['from'],
                "Bab {$chapter['key']} punya rentang hari terbalik.",
            );
        }

        // Rentang bab tidak boleh tumpang tindih.
        $ranges = $chapters->map(fn ($c) => [$c['from'], $c['to']])->sort()->values();

        for ($i = 1; $i < $ranges->count(); $i++) {
            $this->assertGreaterThan(
                $ranges[$i - 1][1],
                $ranges[$i][0],
                'Rentang hari antar bab bertumpang tindih.',
            );
        }
    }
}