<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete untuk akun.
 *
 * Sebelum ini AccountController::destroy() melakukan hard delete, dan FK
 * memakai cascadeOnDelete ke tujuh tabel: xp_transactions, user_quests,
 * quiz_attempts, user_achievements, reward_redemptions, engagement_claims,
 * quest_submissions.
 *
 * Akibatnya satu salah klik menghapus SELURUH riwayat XP, bukti penyelesaian
 * modul, dan klaim hadiah mahasiswa itu - permanen. Saat sengketa ("saya sudah
 * selesaikan modul 8") tidak tersisa bukti apa pun. Soft delete membuat data
 * tetap ada, hanya disembunyikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};