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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->string('module_code')->default('MOD-04');
            $table->string('module_title')->default('Pengenalan Fakulti & Sistem Kredit Semester (SKS)');
            $table->unsignedInteger('question_number')->default(1);
            $table->string('category')->default('Akademik Fakulti');
            $table->text('question');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->char('correct_option', 1)->default('B');
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('xp_reward')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
