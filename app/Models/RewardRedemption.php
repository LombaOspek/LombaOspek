<?php

namespace App\Models;

use App\Support\RewardQr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu klaim hadiah oleh satu mahasiswa.
 *
 * `claim_code` adalah bukti fisik/digital yang ditunjukkan ke panitia. Kode
 * dibuat dengan jaminan unik di database, bukan sekadar dianggap unik di PHP.
 */
class RewardRedemption extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reward_id',
        'user_id',
        'claim_code',
        'coins_spent',
        'status',
        'fulfilled_at',
        'cancelled_at',
        'cancelled_reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'coins_spent' => 'integer',
            'fulfilled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * QR berisi URL verifikasi klaim, bukan kode mentah.
     *
     * Panitia memindai QR mahasiswa dan langsung melihat hadiah, status, dan
     * pemiliknya — cukup untuk memutuskan menyerahkan barang tanpa membuka
     * panel admin. Kalau isinya hanya kode, hasil pindai tidak memberi tahu
     * apa pun dan panitia tetap harus mencarinya manual.
     */
    public function getQrSvgAttribute(): string
    {
        return RewardQr::svg(
            RewardQr::claimPayload($this->claim_code),
            240,
        );
    }

    /** Data URI untuk <img src> — tidak perlu endpoint gambar terpisah. */
    public function getQrDataUriAttribute(): string
    {
        return RewardQr::dataUri(
            RewardQr::claimPayload($this->claim_code),
            240,
        );
    }

    /** URL yang dibuka saat QR dipindai. */
    public function getVerifyUrlAttribute(): string
    {
        return RewardQr::claimPayload($this->claim_code);
    }
}