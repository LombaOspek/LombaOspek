<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ProgressionService;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Jadwal orientasi untuk mahasiswa.
 *
 * Halaman ini menjawab pertanyaan yang sebelumnya tidak bisa dijawab aplikasi:
 * "hari ini ada acara apa, dan setelah itu saya harus mengerjakan modul apa?"
 * Sebelumnya mahasiswa hanya melihat "modul hari ke-3 terbuka" tanpa tahu
 * seminar apa yang mendahuluinya.
 */
class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $schedule,
        private readonly ProgressionService $progression,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $today = $this->progression->effectiveDay();
        $total = $this->progression->totalOrientationDays();

        return view('pages.schedule', [
            'days' => $this->schedule->allDays($user),
            'today' => $today,
            'totalDays' => $total,
            'todayItems' => $this->schedule->forDay($user, $today),
            'nextAction' => $this->schedule->nextActionable($user),
            'counts' => $this->schedule->countsByDay(),
            'orientationOver' => $this->progression->hasEnded(),
        ]);
    }
}