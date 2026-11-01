<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan mutasi koin. Cermin dari XpTransaction, tetapi terpisah total:
 * XP tidak pernah berkurang, koin bisa.
 *
 * `balance_after` disimpan agar riwayat bisa diaudit tanpa menjumlah ulang
 * seluruh tabel setiap kali ditampilkan.
 */
class CoinTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'balance_after',
        'source_type',
        'source_id',
        'description',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'source_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}