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
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module_code')->default('MOD-04');
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('total_questions')->default(5);
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('time_taken_seconds')->default(0);
            $table->json('answers')->nullable();
            $table->boolean('is_passed')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
