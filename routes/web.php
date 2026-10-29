<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Portal Mahasiswa (wajib login)
|--------------------------------------------------------------------------
|
| Sebelumnya seluruh rute ini berada di luar middleware `auth` dan setiap
| controller melakukan fallback `User::first()`, sehingga siapa pun bisa
| melihat data mahasiswa lain tanpa login.
|
*/

Route::middleware(['auth', 'activity'])->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Peta Misi
    Route::get('/misi', [QuestController::class, 'index'])->name('quests.index');
    Route::get('/misi/{quest}', [QuestController::class, 'show'])->name('quests.show');
    Route::post('/misi/{quest}/mulai', [QuestController::class, 'start'])->name('quests.start');
    Route::post('/misi/{quest}/selesai', [QuestController::class, 'complete'])->name('quests.complete');

    // Kuis Interaktif
    Route::get('/kuis/{module?}', [QuizController::class, 'index'])->name('quiz');
    Route::post('/kuis/mulai', [QuizController::class, 'start'])->name('quiz.start');
    Route::post('/kuis/jawab', [QuizController::class, 'submit'])->name('quiz.submit');

    // Pencapaian & Sertifikat
    Route::get('/pencapaian', [AchievementController::class, 'index'])->name('achievements');
    Route::get('/sijil', [CertificateController::class, 'show'])->name('certificate');

    // Peringkat
    Route::get('/peringkat', [LeaderboardController::class, 'index'])->name('leaderboard');

    // Profil, Tetapan & Pusat Bantuan
    Route::get('/profil', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profil/tetapan', [ProfileController::class, 'updateSettings'])->name('profile.settings');
    Route::post('/profil/sandi', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/bantuan/tiket', [ProfileController::class, 'submitHelpTicket'])->name('help.ticket');
});

/*
|--------------------------------------------------------------------------
| Mentor
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'mentor'])->prefix('mentor')->name('mentor.')->group(function (): void {
    Route::get('/tiket', [MentorController::class, 'tickets'])->name('tickets');
    Route::post('/tiket/{ticket}/balas', [MentorController::class, 'reply'])->name('tickets.reply');
});

/*
|--------------------------------------------------------------------------
| Verifikasi Sertifikat (publik, tanpa data sensitif)
|--------------------------------------------------------------------------
*/

Route::get('/verifikasi/{code?}', [CertificateController::class, 'verify'])->name('certificate.verify');
