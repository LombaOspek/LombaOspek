<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Klaim hadiah engagement (kotak misteri, misi rahasia).
 *
 * Kombinasi (user_id, kind, period) unik: satu user hanya bisa mengklaim satu
 * kali per periode. Inilah pengunci yang membuat hadiah tidak bisa di-farm
 * dari sisi klien.
 */
class EngagementClaim extends Model
{
    protected $fillable = [
        'user_id',
        'kind',
        'period',
        'value',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
