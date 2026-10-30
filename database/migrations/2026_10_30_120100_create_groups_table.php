<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gugus (kelompok orientasi) sebagai entitas nyata, bukan sekadar string
     * di kolom users.group_name. Dengan tabel ini seorang LO bisa ditugaskan
     * ke satu gugus dan hanya memverifikasi mahasiswa gugus tersebut.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->nullable()->unique();
            $table->string('study_program')->nullable();
            $table->unsignedSmallInteger('capacity')->default(75);
            $table->string('badge_color', 20)->default('amber');
            $table->string('invitation_code', 20)->nullable()->unique();
            $table->string('mentor_name')->nullable();
            $table->foreignId('mentor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('study_program');
            $table->index('mentor_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('group_name')
                ->constrained('groups')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('last_active_on');
            $table->string('password_changed_at_ip', 45)->nullable()->after('last_login_at');

            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropIndex(['group_id']);
            $table->dropColumn(['group_id', 'last_login_at', 'password_changed_at_ip']);
        });

        Schema::dropIfExists('groups');
    }
};