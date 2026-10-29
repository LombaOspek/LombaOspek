<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpTicket extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'message',
        'status',
        'reply',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
