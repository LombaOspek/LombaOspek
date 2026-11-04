<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema awal memberi kolom `xp` nilai default 1320 dan `level` default 4.
 *
 * Akibatnya SETIAP akun baru — hasil import massal, akun manual, maupun akun
 * demo — langsung punya 1.320 XP yang tidak punya satu pun baris di
 * `xp_transactions`. Itu merusak dua hal sekaligus:
 *
 *   1. Rekonsiliasi `users.xp == SUM(xp_transactions.amount)` tidak pernah
 *      bisa benar, padahal justru itulah yang dibuktikan ke juri.
 *   2. Papan peringkat menampilkan mahasiswa baru sebagai "Pemikir Cepat"
 *      level 4, sehingga peringkat kehilangan makna sejak hari pertama.
 *
 * Default yang benar untuk pemain baru adalah nol. XP hanya boleh bertambah
 * lewat XpService supaya selalu punya jejak ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('xp')->default(0)->change();
            $table->unsignedTinyInteger('level')->default(1)->change();
        });

        /*
         * Baris yang sudah terlanjur dibuat dengan default lama tetap punya XP
         * tanpa ledger. Yang bisa dikenali dengan pasti hanyalah akun yang
         * TIDAK punya transaksi sama sekali: saldonya dikembalikan ke nol.
         *
         * Akun yang sudah bermain punya baris transaksi, jadi tidak disentuh —
         * reset massal akan menghapus progres mahasiswa sungguhan.
         */
        $orphans = DB::table('users')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('xp_transactions')
                ->whereColumn('xp_transactions.user_id', 'users.id'))
            ->where('xp', '>', 0)
            ->pluck('id');

        if ($orphans->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $orphans)
                ->update(['xp' => 0, 'level' => 1]);
        }
    }

    public function down(): void
    {
        // Nilai default lama sengaja tidak dipulihkan: ia adalah bug, bukan
        // keadaan yang layak dikembalikan.
    }
};