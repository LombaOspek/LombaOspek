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
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedInteger('step_number')->default(1);
            $table->string('title');
            $table->string('category');
            $table->text('description');
            $table->string('location')->nullable();
            $table->unsignedInteger('xp_reward')->default(100);
            $table->string('icon')->default('🗺️');
            $table->string('deadline')->nullable();
            $table->string('action_type')->default('general');
            $table->boolean('is_default_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
