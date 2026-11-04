<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pasang gambar mockup ke hadiah yang belum punya.
 *
 * `image_path` sengaja tidak diisi lewat seeder: gambar adalah aset yang
 * diunggah operator lewat panel admin, dan seeder yang menulis path akan
 * menimpa gambar yang sudah diganti panitia. Migrasi ini hanya MENGISI YANG
 * KOSONG, jadi aman dijalankan pada database yang sudah berisi.
 */
return new class extends Migration
{
    /** @var array<string, string> kode hadiah => path di disk public */
    private const IMAGES = [
        'LANYARD-01' => 'rewards/lanyard-01.jpg',
        'LANYARD-02' => 'rewards/lanyard-02.jpg',
        'PIN-01' => 'rewards/pin-01.jpg',
        'STIKER-01' => 'rewards/stiker-01.jpg',
        'MUG-01' => 'rewards/mug-01.jpg',
        'TOTE-01' => 'rewards/tote-01.jpg',
        'KAOS-01' => 'rewards/kaos-01.jpg',
        'VOUCHER-KANTIN' => 'rewards/voucher-kantin.jpg',
        'PULSA-10K' => 'rewards/pulsa-10k.jpg',
    ];

    public function up(): void
    {
        foreach (self::IMAGES as $code => $path) {
            DB::table('rewards')
                ->where('code', $code)
                ->whereNull('image_path')
                ->update(['image_path' => $path, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Gambar tidak dihapus: operator mungkin sudah menggantinya, dan
        // menghapus aset saat rollback justru merusak data yang sah.
    }
};