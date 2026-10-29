<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->after('password');
            $table->date('last_active_on')->nullable()->after('streak_days');
            $table->timestamp('orientation_completed_at')->nullable()->after('dark_mode');
            $table->string('avatar_path')->nullable()->after('avatar');

            $table->index(['role', 'xp']);
            $table->index('last_active_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'xp']);
            $table->dropIndex(['last_active_on']);

            $table->dropColumn([
                'role',
                'last_active_on',
                'orientation_completed_at',
                'avatar_path',
            ]);
        });
    }
};
