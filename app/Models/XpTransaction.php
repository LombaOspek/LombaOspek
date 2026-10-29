<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan mutasi XP. Sumber (source_type/source_id) memungkinkan audit
 * "poin ini dari mana" dan `idempotency_key` memblokir award ganda.
 */
class XpTransaction extends Model
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
