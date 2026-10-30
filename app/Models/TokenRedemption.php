<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak klaim token. Tabel inilah yang menegakkan aturan
 * "satu token hanya berlaku satu kali per akun".
 */
class TokenRedemption extends Model
{
    protected $fillable = [
        'quest_id',
        'user_id',
        'token_hash',
        'ip_address',
    ];

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}