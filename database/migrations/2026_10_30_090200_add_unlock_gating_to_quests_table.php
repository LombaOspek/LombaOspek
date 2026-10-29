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
        Schema::table('quests', function (Blueprint $table) {
            $table->unsignedTinyInteger('unlock_day')->default(1)->after('step_number');
            $table->string('quiz_module_code', 20)->nullable()->after('action_type');
            $table->unsignedInteger('estimated_minutes')->default(15)->after('xp_reward');
            $table->text('objective')->nullable()->after('description');

            $table->index(['step_number', 'unlock_day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropIndex(['step_number', 'unlock_day']);

            $table->dropColumn([
                'unlock_day',
                'quiz_module_code',
                'estimated_minutes',
                'objective',
            ]);
        });
    }
};
