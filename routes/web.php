<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LiaisonOfficerController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestBuilderController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\VerificationController;
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
*/

Route::middleware(['auth', 'activity'])->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Peta Misi
    Route::get('/misi', [QuestController::class, 'index'])->name('quests.index');
    Route::get('/misi/{quest}', [QuestController::class, 'show'])->name('quests.show');
    Route::post('/misi/{quest}/mulai', [QuestController::class, 'start'])->name('quests.start');
    Route::post('/misi/{quest}/selesai', [QuestController::class, 'complete'])->name('quests.complete');

    /*
     * Verifikasi anti-curang. Dibatasi rate limit karena ini permukaan yang
     * paling mungkin dipakai untuk menebak token atau mengirim koordinat palsu.
     */
    Route::prefix('verifikasi-misi')->name('verify.')->middleware('throttle:12,1')->group(function (): void {
        Route::post('/{quest}/token', [VerificationController::class, 'token'])->name('token');
        Route::post('/{quest}/lokasi', [VerificationController::class, 'location'])->name('location');
        Route::post('/{quest}/foto', [VerificationController::class, 'photo'])->name('photo');
    });

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
| Portal Liaison Officer (kakak tingkat)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:lo,admin'])
    ->prefix('lo')
    ->name('lo.')
    ->group(function (): void {
        Route::get('/verifikasi', [LiaisonOfficerController::class, 'index'])->name('dashboard');
        Route::get('/bukti/{submission}', [LiaisonOfficerController::class, 'proof'])->name('proof');
        Route::post('/bukti/{submission}/luluskan', [LiaisonOfficerController::class, 'approve'])->name('approve');
        Route::post('/bukti/{submission}/tolak', [LiaisonOfficerController::class, 'reject'])->name('reject');

        // Tiket bantuan tetap tersedia bagi LO.
        Route::get('/tiket', [MentorController::class, 'tickets'])->name('tickets');
        Route::post('/tiket/{ticket}/balas', [MentorController::class, 'reply'])->name('tickets.reply');
    });

/*
|--------------------------------------------------------------------------
| Panel Admin (superadmin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/akun', [AccountController::class, 'index'])->name('accounts');
        Route::post('/akun/gugus', [AccountController::class, 'storeGroup'])->name('groups.store');
        Route::post('/akun/import', [AccountController::class, 'bulkImport'])->name('accounts.import');
        Route::get('/akun/template', [AccountController::class, 'template'])->name('accounts.template');
        Route::post('/akun/{user}/peran', [AccountController::class, 'updateRole'])->name('accounts.role');
        Route::delete('/akun/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');

        Route::get('/misi', [QuestBuilderController::class, 'index'])->name('quests.index');
        Route::post('/misi', [QuestBuilderController::class, 'store'])->name('quests.store');
        Route::put('/misi/{quest}', [QuestBuilderController::class, 'update'])->name('quests.update');
    });

/*
|--------------------------------------------------------------------------
| Verifikasi Sertifikat (publik)
|--------------------------------------------------------------------------
*/

Route::get('/verifikasi/{code?}', [CertificateController::class, 'verify'])->name('certificate.verify');