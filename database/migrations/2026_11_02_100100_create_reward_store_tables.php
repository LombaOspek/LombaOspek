<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bursa Hadiah — memisahkan XP (progres, tidak bisa berkurang) dari Koin
 * (mata uang yang bisa dibelanjakan).
 *
 * Model ekonominya:
 *   XP   = reputasi. Hanya bertambah. Menentukan level, gelar, papan peringkat.
 *   Koin = saldo belanja. Bertambah dari aktivitas, berkurang saat menukar hadiah.
 *
 * Kalau XP dipakai langsung sebagai alat tukar, mahasiswa bisa menukar lencana
 * miliknya sendiri lalu turun level — dan papan peringkat kehilangan makna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Saldo berjalan. Diperlakukan sebagai cache dari coin_transactions,
            // tetapi tetap disimpan agar pembacaan daftar tidak perlu SUM().
            $table->unsignedBigInteger('coins')->default(0)->after('xp');
            $table->index('coins');
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 30)->default('merchandise');

            $table->unsignedInteger('cost_coins');
            $table->string('image_path')->nullable();

            /*
             * stock = sisa unit yang boleh diklaim SELURUH mahasiswa.
             * NULL berarti tak terbatas (mis. voucher digital).
             */
            $table->unsignedInteger('stock')->nullable();

            /*
             * quota_per_user = batas klaim per mahasiswa. NULL = tanpa batas.
             * Dipisah dari stock: stok mengatur ketersediaan global, kuota
             * mencegah satu orang menyapu seluruh stok.
             */
            $table->unsignedSmallInteger('quota_per_user')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Kode klaim unik yang ditunjukkan mahasiswa di meja panitia.
            $table->string('claim_code', 25)->unique();

            // Jumlah koin yang dibekukan saat klaim. Disimpan agar riwayat tetap
            // benar walau harga hadiah diubah kemudian.
            $table->unsignedInteger('coins_spent');

            // pending = sudah dibayar, belum diambil. fulfilled = sudah diserahkan.
            $table->string('status', 20)->default('pending');
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason', 300)->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indeks untuk dua pembacaan yang paling sering: "klaim saya" dan
            // hitung kuota per user per hadiah.
            $table->index(['user_id', 'status']);
            $table->index(['reward_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Bertanda: positif = dapat koin, negatif = belanja.
            $table->integer('amount');
            $table->unsignedBigInteger('balance_after');

            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description');

            /*
             * Kunci idempotensi: mutasi dengan key yang sama tidak pernah
             * dicatat dua kali. Inilah yang membuat klik ganda atau retry
             * jaringan tidak menggandakan saldo.
             */
            $table->string('idempotency_key', 120)->nullable()->unique();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['coins']);
            $table->dropColumn('coins');
        });
    }
};
