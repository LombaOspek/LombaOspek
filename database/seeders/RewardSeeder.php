<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

/**
 * Katalog awal Bursa Hadiah.
 *
 * Harga disusun supaya mahasiswa yang menyelesaikan sekitar separuh modul
 * sudah bisa menukar sesuatu yang nyata — hadiah pertama harus terasa
 * terjangkau, kalau tidak bursa hanya jadi pajangan.
 *
 * `stock` null = tak terbatas (voucher digital). `quota_per_user` membatasi
 * supaya satu orang tidak menyapu stok fisik.
 */
class RewardSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            [
                'code' => 'LANYARD-01',
                'name' => 'Lanyard OspekQuest',
                'description' => 'Lanyard resmi orientasi dengan cetakan logo Fakultas Teknik.',
                'category' => 'merchandise',
                'cost_coins' => 150,
                'stock' => 300,
                'quota_per_user' => 1,
                'sort_order' => 10,
            ],
            [
                'code' => 'PIN-01',
                'name' => 'Pin Enamel WIRA',
                'description' => 'Pin logam maskot WIRA, ukuran 3 cm.',
                'category' => 'merchandise',
                'cost_coins' => 200,
                'stock' => 250,
                'quota_per_user' => 2,
                'sort_order' => 20,
            ],
            [
                'code' => 'STIKER-01',
                'name' => 'Paket Stiker Kampus',
                'description' => 'Lima stiker vinyl tahan air berisi ikon kampus dan WIRA.',
                'category' => 'merchandise',
                'cost_coins' => 100,
                'stock' => 400,
                'quota_per_user' => 2,
                'sort_order' => 30,
            ],
            [
                'code' => 'MUG-01',
                'name' => 'Mug Keramik OspekQuest',
                'description' => 'Mug keramik 330 ml dengan sablon satu warna.',
                'category' => 'merchandise',
                'cost_coins' => 350,
                'stock' => 120,
                'quota_per_user' => 1,
                'sort_order' => 40,
            ],
            [
                'code' => 'TOTE-01',
                'name' => 'Tote Bag Kanvas',
                'description' => 'Tote bag kanvas tebal, sablon "Smart Ecosystems".',
                'category' => 'merchandise',
                'cost_coins' => 500,
                'stock' => 80,
                'quota_per_user' => 1,
                'sort_order' => 50,
            ],
            [
                'code' => 'KAOS-01',
                'name' => 'Kaos Orientasi 2026',
                'description' => 'Kaos katun combed 30s, ukuran M/L/XL. Ambil di sekretariat.',
                'category' => 'apparel',
                'cost_coins' => 800,
                'stock' => 60,
                'quota_per_user' => 1,
                'sort_order' => 60,
            ],
            [
                'code' => 'VOUCHER-KANTIN',
                'name' => 'Voucher Kantin Rp15.000',
                'description' => 'Voucher digital yang bisa ditukar di kantin Fakultas Teknik.',
                'category' => 'voucher',
                'cost_coins' => 250,
                'stock' => null,
                'quota_per_user' => 3,
                'sort_order' => 70,
            ],
            [
                'code' => 'PULSA-10K',
                'name' => 'Pulsa Digital Rp10.000',
                'description' => 'Dikirim ke nomor yang terdaftar di akun Anda dalam 3 hari kerja.',
                'category' => 'digital',
                'cost_coins' => 300,
                'stock' => 150,
                'quota_per_user' => 1,
                'sort_order' => 80,
            ],
        ];

        foreach ($rewards as $reward) {
            // updateOrCreate supaya seeder aman dijalankan ulang tanpa
            // menggandakan baris atau mereset stok yang sudah terpakai.
            Reward::updateOrCreate(
                ['code' => $reward['code']],
                $reward + ['is_active' => true],
            );
        }

        $this->attachCatalogImages();
    }

    /**
     * Pasang gambar katalog untuk hadiah yang belum punya.
     *
     * Migrasi `attach_reward_images` tidak bisa diandalkan sendirian: pada
     * `migrate:fresh` ia berjalan SEBELUM ada baris hadiah, sehingga meng-update
     * nol baris dan setiap hadiah kembali tanpa gambar. Seeder yang menanggung
     * ini karena ia berjalan setelah barisnya ada.
     *
     * Hanya mengisi yang KOSONG: gambar yang sudah diganti panitia lewat panel
     * admin tidak boleh ditimpa setiap kali seeder dijalankan.
     */
    private function attachCatalogImages(): void
    {
        $images = [
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

        foreach ($images as $code => $path) {
            Reward::query()
                ->where('code', $code)
                ->whereNull('image_path')
                ->update(['image_path' => $path]);
        }
    }
}