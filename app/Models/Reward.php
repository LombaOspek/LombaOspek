<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Hadiah yang bisa ditukar dengan koin.
 *
 * Stok dan kuota sengaja dua kolom berbeda:
 *   - `stock`         : sisa unit untuk SELURUH mahasiswa (null = tak terbatas)
 *   - `quota_per_user`: batas klaim per mahasiswa (null = tanpa batas)
 *
 * Tanpa pemisahan ini, satu mahasiswa bisa menyapu seluruh stok.
 */
class Reward extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'cost_coins',
        'image_path',
        'stock',
        'quota_per_user',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'cost_coins' => 'integer',
            'stock' => 'integer',
            'quota_per_user' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    /**
     * URL gambar hadiah yang bisa dipakai langsung di <img src>.
     *
     * `image_path` menyimpan path RELATIF terhadap disk public
     * ("rewards/foo.jpg"), bukan path publik. Memakainya dengan asset()
     * menghasilkan "/rewards/foo.jpg" yang 404 — berkas aslinya ada di
     * "/storage/rewards/foo.jpg". Accessor ini menutup celah itu supaya
     * view tidak perlu tahu detail disk, dan tidak ada view yang bisa
     * menyimpang lagi.
     *
     * Mengembalikan null kalau tidak ada gambar, sehingga Blade cukup
     * memeriksa satu nilai untuk memilih antara <img> dan blok inisial.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        /*
         * URL RELATIF, bukan absolut lewat Storage::url().
         *
         * Storage::url() memakai APP_URL dari config. Di proyek ini
         * APP_URL=http://localhost:8000 sementara server dev berjalan di
         * port lain, sehingga src menunjuk port yang salah: gambar tampil
         * hanya kalau kebetulan ada server di port itu, dan rusak di hosting
         * yang domainnya berbeda. Path relatif selalu benar pada dokumen yang
         * memuatnya — dev, port berapa pun, subfolder, maupun produksi.
         */
        return '/storage/'.ltrim($this->image_path, '/');
    }

    /**
     * Hanya hadiah yang boleh muncul di bursa.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Stok tak terbatas ditandai NULL, bukan angka besar. Ini memisahkan
     * "tidak dibatasi" dari "kebetulan sisa banyak" saat stok dikurangi.
     */
    public function hasUnlimitedStock(): bool
    {
        return $this->stock === null;
    }

    public function hasStock(): bool
    {
        return $this->stock === null || $this->stock > 0;
    }

    public function isSoldOut(): bool
    {
        return $this->stock !== null && $this->stock <= 0;
    }

    public function hasUnlimitedQuota(): bool
    {
        return $this->quota_per_user === null;
    }

    /**
     * Sisa unit yang tampil di UI. Null tetap null supaya tampilan bisa
     * menulis "Tersedia" alih-alih angka palsu.
     */
    public function remainingStockLabel(): ?int
    {
        return $this->stock;
    }
}