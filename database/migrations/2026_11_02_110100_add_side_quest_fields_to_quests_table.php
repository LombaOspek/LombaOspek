<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Side Quest — misi sosial untuk interaksi antarmahasiswa baru.
 *
 * Disebut eksplisit di proposal (Manfaat & Tujuan poin 3): "mahasiswa didorong
 * untuk berinteraksi secara sosial (melalui fitur Side Quest)".
 *
 * Side Quest SENGAJA dipisah dari kolom biasa karena aturannya berbeda:
 *   - tidak masuk rantai prasyarat modul utama
 *   - tidak dihitung dalam gerbang babak
 *   - tidak memblokir modul berikutnya
 *
 * Menumpangkannya pada `step_number` biasa akan membuat peta salah hitung dan
 * satu misi opsional bisa mengunci seluruh perjalanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->boolean('is_side_quest')->default(false)->after('is_default_completed');
            $table->unsignedSmallInteger('required_completed_modules')->default(0)->after('is_side_quest');

            $table->index(['is_side_quest', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropIndex(['is_side_quest', 'status']);
            $table->dropColumn(['is_side_quest', 'required_completed_modules']);
        });
    }
};
