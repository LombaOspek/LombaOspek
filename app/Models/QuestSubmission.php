<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'quest_id',
        'method',
        'status',
        'proof_path',
        'latitude',
        'longitude',
        'distance_meters',
        'token_used_hash',
        'note',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'method' => VerificationMethod::class,
            'status' => SubmissionStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'distance_meters' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Pending);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Approved);
    }
}