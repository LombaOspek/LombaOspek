<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpTicket extends Model
{
    protected $fillable = [
        'user_id',
        'answered_by',
        'subject',
        'category',
        'message',
        'status',
        'reply',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'answered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Open);
    }
}
