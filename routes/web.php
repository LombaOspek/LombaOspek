<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/quick-login', [AuthController::class, 'quickLogin'])->name('quick-login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Main Authenticated / Student Experience routes
Route::middleware(['web'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Misi & Peta Orientasi
    Route::get('/misi', [QuestController::class, 'index'])->name('quests.index');
    Route::post('/misi/{quest}/complete', [QuestController::class, 'complete'])->name('quests.complete');

    // Kuiz Interaktif
    Route::get('/kuiz', [QuizController::class, 'index'])->name('quiz');
    Route::post('/kuiz/submit', [QuizController::class, 'submit'])->name('quiz.submit');

    // Pencapaian & Sijil
    Route::get('/pencapaian', [AchievementController::class, 'index'])->name('achievements');
    Route::get('/sijil', [AchievementController::class, 'certificate'])->name('certificate');

    // Profil, Tetapan & Pusat Bantuan
    Route::get('/profil', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profil/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profil/settings', [ProfileController::class, 'updateSettings'])->name('profile.settings');
    Route::post('/bantuan/tiket', [ProfileController::class, 'submitHelpTicket'])->name('help.ticket');
});
