<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak klaim hadiah engagement (Kotak Misteri harian, Misi Rahasia, dll).
     *
     * Tabel ini BUKAN duplikat xp_transactions. Ledger XP mencatat mutasi poin;
     * tabel ini menegakkan aturan "satu klaim per periode", sehingga mengklik
     * kotak misteri dua kali atau me-reload halaman tidak menghasilkan hadiah
     * kedua walau klien dipaksa mengirim permintaan berulang.
     */
    public function up(): void
    {
        Schema::create('engagement_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 40);

            // Periode klaim: YYYY-MM-DD untuk hadiah harian, 'once' untuk
            // hadiah sekali seumur akun seperti Misi Rahasia.
            $table->string('period', 20);

            // Nilai acak yang sudah ditentukan server (mis. hadiah XP kotak).
            $table->unsignedInteger('value')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            // Inilah pengunci sebenarnya: periode yang sama tidak bisa diklaim
            // dua kali oleh user yang sama.
            $table->unique(['user_id', 'kind', 'period']);
            $table->index(['kind', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_claims');
    }
};
