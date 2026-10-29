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
        Schema::table('achievements', function (Blueprint $table) {
            $table->string('metric', 40)->default('quests_completed')->after('category');
            $table->boolean('is_automatic')->default(true)->after('total_steps');

            $table->index('metric');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempt_number')->default(1)->after('module_code');
            $table->timestamp('expires_at')->nullable()->after('time_taken_seconds');

            $table->index(['user_id', 'module_code', 'created_at']);
        });

        Schema::table('user_quests', function (Blueprint $table) {
            $table->unsignedInteger('xp_awarded')->default(0)->after('status');
            $table->string('note', 500)->nullable()->after('completed_at');

            $table->index(['user_id', 'status']);
        });

        Schema::table('help_tickets', function (Blueprint $table) {
            $table->foreignId('answered_by')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable()->after('reply');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });

        Schema::table('help_tickets', function (Blueprint $table) {
            $table->dropForeign(['answered_by']);
            $table->dropColumn(['answered_by', 'answered_at']);
        });

        Schema::table('user_quests', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn(['xp_awarded', 'note']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'module_code', 'created_at']);
            $table->dropColumn(['attempt_number', 'expires_at']);
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->dropIndex(['metric']);
            $table->dropColumn(['metric', 'is_automatic']);
        });
    }
};
