<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\OrientationSchedule;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jadwal orientasi.
 *
 * Sebelum ini "jadwal" hanya berupa angka `quests.unlock_day` dan teks
 * "Hari ke-3" — panitia tidak punya tempat mencatat APA yang terjadi pada hari
 * itu, dan mahasiswa melihat modul terbuka tanpa tahu acara yang mendahuluinya.
 */
class OrientationScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function student(): User
    {
        return User::query()->where('role', UserRole::Student)->firstOrFail();
    }

    public function test_every_schedule_row_lands_on_a_valid_day(): void
    {
        $total = (int) config('ospek.orientation.days');

        $this->assertGreaterThan(0, OrientationSchedule::count(), 'Jadwal kosong: seeder tidak berjalan.');

        foreach (OrientationSchedule::all() as $row) {
            $this->assertGreaterThanOrEqual(1, $row->day, "Agenda \"{$row->title}\" punya hari < 1.");
            $this->assertLessThanOrEqual($total, $row->day, "Agenda \"{$row->title}\" melewati hari terakhir.");
        }
    }

    public function test_every_mainline_quest_is_linked_to_an_agenda(): void
    {
        /*
         * Inilah inti "selaras": setiap modul peta misi punya acara yang
         * mendahuluinya. Modul yang tidak tertaut berarti mahasiswa melihat
         * tugas tanpa konteks kapan dikerjakan.
         */
        $linked = OrientationSchedule::query()->whereNotNull('quest_id')->pluck('quest_id');

        $orphans = Quest::mainline()->whereNotIn('id', $linked)->pluck('code');

        $this->assertTrue($orphans->isEmpty(), 'Modul tanpa agenda: '.$orphans->implode(', '));
    }

    public function test_a_quest_is_not_linked_to_two_agendas(): void
    {
        /*
         * Satu modul muncul di dua agenda membuat mahasiswa melihat tombol yang
         * sama di dua tempat dan mengira ada dua tugas berbeda. Ditegakkan di
         * level database (unique pada quest_id), diuji di sini sebagai jaring.
         */
        $dupes = OrientationSchedule::query()
            ->whereNotNull('quest_id')
            ->selectRaw('quest_id, COUNT(*) as total')
            ->groupBy('quest_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('quest_id');

        $this->assertTrue($dupes->isEmpty(), 'Modul tertaut ganda: '.$dupes->implode(', '));
    }

    public function test_agenda_are_ordered_by_time_within_a_day(): void
    {
        foreach (OrientationSchedule::query()->distinct()->pluck('day') as $day) {
            $rows = OrientationSchedule::query()->forDay($day)->get();

            $this->assertGreaterThan(0, $rows->count(), "Hari {$day} tidak punya agenda.");

            $times = $rows->pluck('starts_at')->filter()->values();
            $sorted = $times->sort()->values();

            $this->assertEquals($sorted->all(), $times->all(), "Agenda hari {$day} tidak urut jam.");
        }
    }

    public function test_the_time_range_reads_naturally(): void
    {
        $row = OrientationSchedule::query()->whereNotNull('starts_at')->whereNotNull('ends_at')->firstOrFail();

        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}–\d{2}:\d{2}$/', $row->time_range);
        $this->assertSame('Seminar', OrientationSchedule::query()->where('kind', 'seminar')->first()?->kind_label);
    }

    public function test_the_schedule_page_opens_for_a_student(): void
    {
        $html = $this->actingAs($this->student())->get('/jadwal')->assertOk()->getContent();

        $this->assertStringContainsString('Jadwal Orientasi', $html);
        $this->assertStringContainsString('Rangkaian lengkap', $html);

        // Setiap agenda yang punya waktu harus tampil jamnya.
        $row = OrientationSchedule::query()->whereNotNull('starts_at')->firstOrFail();
        $this->assertStringContainsString(substr((string) $row->starts_at, 0, 5), $html);
    }

    public function test_the_schedule_page_requires_login(): void
    {
        $this->get('/jadwal')->assertRedirect('/login');
    }

    public function test_the_schedule_page_lists_all_eight_days(): void
    {
        $html = $this->actingAs($this->student())->get('/jadwal')->assertOk()->getContent();

        for ($day = 1; $day <= (int) config('ospek.orientation.days'); $day++) {
            $this->assertStringContainsString("hari-{$day}", $html, "Hari ke-{$day} tidak dirender.");
        }
    }

    public function test_the_schedule_service_reports_quest_status_per_agenda(): void
    {
        $service = app(ScheduleService::class);
        $student = $this->student();

        $day = OrientationSchedule::query()->whereNotNull('quest_id')->value('day');
        $items = $service->forDay($student, $day);

        $this->assertNotEmpty($items);

        $statuses = $items->pluck('quest_status')->unique()->all();

        foreach ($statuses as $status) {
            $this->assertContains($status, ['done', 'open', 'locked', 'none']);
        }

        // Agenda tanpa modul harus berstatus 'none', bukan 'locked'.
        $withoutQuest = collect($service->forDay($student, $day))
            ->firstWhere(fn (array $i) => $i['quest'] === null);

        if ($withoutQuest !== null) {
            $this->assertSame('none', $withoutQuest['quest_status']);
        }
    }

    public function test_all_days_covers_every_orientation_day(): void
    {
        $days = app(ScheduleService::class)->allDays($this->student());

        $this->assertCount((int) config('ospek.orientation.days'), $days);
        $this->assertSame(range(1, (int) config('ospek.orientation.days')), $days->pluck('day')->all());

        // Tepat satu hari ditandai "hari ini".
        $this->assertSame(1, $days->where('is_today', true)->count());
    }

    public function test_next_actionable_points_at_an_unfinished_linked_quest(): void
    {
        $service = app(ScheduleService::class);
        $student = $this->student();

        $next = $service->nextActionable($student);

        if ($next === null) {
            // Semua modul hari ini sudah selesai — sah, tetapi harus benar-benar
            // berarti "tidak ada yang tersisa", bukan query yang salah.
            $today = app(ProgressionService::class)->effectiveDay();
            $doneIds = $student->quests()->wherePivot('status', 'completed')->pluck('quests.id');
            $remaining = OrientationSchedule::query()
                ->where('day', $today)->whereNotNull('quest_id')
                ->whereNotIn('quest_id', $doneIds)->count();

            $this->assertSame(0, $remaining, 'nextActionable() null padahal masih ada modul tersisa.');
        } else {
            $this->assertInstanceOf(Quest::class, $next['quest']);
            $this->assertInstanceOf(OrientationSchedule::class, $next['schedule']);
        }
    }

    public function test_the_dashboard_shows_todays_agenda(): void
    {
        /*
         * Dashboard harus menyebut acara hari ini, bukan hanya nomor hari.
         * Tanpa ini, "selaras" hanya benar di halaman jadwal dan dashboard tetap
         * menampilkan modul tanpa konteks.
         */
        $student = $this->student();
        $today = app(ProgressionService::class)->effectiveDay();

        $agenda = OrientationSchedule::query()->forDay($today)->first();

        $html = $this->actingAs($student)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Agenda hari ini', $html);
        $this->assertStringContainsString('Lihat jadwal lengkap', $html);

        if ($agenda !== null) {
            $this->assertStringContainsString(
                e($agenda->title),
                $html,
                'Agenda hari ini tidak muncul di dashboard.',
            );
        }
    }

    public function test_the_sidebar_links_to_the_schedule(): void
    {
        $html = $this->actingAs($this->student())->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString(route('schedule.index'), $html);
        $this->assertStringContainsString('Jadwal', $html);
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $before = OrientationSchedule::count();

        $this->seed(\Database\Seeders\OrientationScheduleSeeder::class);
        $this->seed(\Database\Seeders\OrientationScheduleSeeder::class);

        $this->assertSame($before, OrientationSchedule::count(), 'Seeder jadwal menggandakan baris.');
    }
}