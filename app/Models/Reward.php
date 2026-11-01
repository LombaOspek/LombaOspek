<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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