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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('icon')->default('🏆');
            $table->string('category')->default('Misi');
            $table->unsignedInteger('xp_reward')->default(150);
            $table->unsignedInteger('total_steps')->default(1);
            $table->string('badge_name')->nullable();
            $table->string('badge_color')->default('neo-yellow');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
