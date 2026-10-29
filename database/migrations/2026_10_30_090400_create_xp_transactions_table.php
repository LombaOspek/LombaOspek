<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Buku besar XP. Setiap penambahan/pengurangan poin dicatat di sini sehingga
     * saldo pada kolom users.xp selalu dapat direkonsiliasi dan tidak mungkin
     * terjadi double-award dari request ganda.
     */
    public function up(): void
    {
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->unsignedInteger('balance_after');
            $table->string('source_type', 60);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description');
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['source_type', 'source_id']);
        });

        if (Schema::hasTable('users')) {
            (new User)->newQuery()->update(['role' => 'student']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xp_transactions');
    }
};
