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
            $table->string('nim')->nullable()->unique()->after('id');
            $table->string('faculty')->nullable()->after('email');
            $table->string('major')->nullable()->after('faculty');
            $table->string('group_name')->nullable()->after('major');
            $table->string('mentor_name')->nullable()->after('group_name');
            $table->string('phone')->nullable()->after('mentor_name');
            $table->unsignedInteger('xp')->default(1320)->after('phone');
            $table->unsignedInteger('level')->default(4)->after('xp');
            $table->unsignedInteger('streak_days')->default(9)->after('level');
            $table->string('avatar')->nullable()->after('streak_days');
            $table->boolean('sound_effects')->default(true)->after('avatar');
            $table->boolean('email_notifications')->default(true)->after('sound_effects');
            $table->boolean('whatsapp_notifications')->default(true)->after('email_notifications');
            $table->boolean('dark_mode')->default(false)->after('whatsapp_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nim',
                'faculty',
                'major',
                'group_name',
                'mentor_name',
                'phone',
                'xp',
                'level',
                'streak_days',
                'avatar',
                'sound_effects',
                'email_notifications',
                'whatsapp_notifications',
                'dark_mode',
            ]);
        });
    }
};
