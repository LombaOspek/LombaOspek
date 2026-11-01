<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kegagalan yang wajar terjadi di bursa hadiah (stok habis, koin kurang).
 *
 * Dibuat sebagai exception, bukan return null, supaya pemanggil tidak bisa
 * "lupa" memeriksa hasil dan menganggap transaksi berhasil. Setiap kasus
 * membawa `reason` yang stabil untuk dipetakan controller ke HTTP status.
 */
class RewardRedemptionException extends RuntimeException
{
    public const INSUFFICIENT_COINS = 'insufficient_coins';

    public const OUT_OF_STOCK = 'out_of_stock';

    public const QUOTA_EXCEEDED = 'quota_exceeded';

    public const INACTIVE = 'inactive';

    public const CANNOT_CANCEL = 'cannot_cancel';

    private function __construct(
        public readonly string $reason,
        string $message,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    /**
     * Jalur untuk kegagalan yang tidak punya helper khusus (mis. kode klaim
     * gagal dibuat). Tetap berupa exception agar pemanggil tidak bisa
     * mengabaikan hasilnya.
     */
    public static function generic(string $reason, string $message, array $context = []): self
    {
        return new self($reason, $message, $context);
    }

    public static function insufficientCoins(int $required, int $available): self
    {
        return new self(
            self::INSUFFICIENT_COINS,
            sprintf(
                'Koin Anda tidak cukup. Dibutuhkan %s, saldo %s.',
                number_format($required, 0, ',', '.'),
                number_format($available, 0, ',', '.'),
            ),
            ['required' => $required, 'available' => $available],
        );
    }

    public static function outOfStock(string $rewardName): self
    {
        return new self(
            self::OUT_OF_STOCK,
            sprintf('Stok "%s" sudah habis.', $rewardName),
        );
    }

    public static function quotaExceeded(string $rewardName, int $quota): self
    {
        return new self(
            self::QUOTA_EXCEEDED,
            sprintf('Anda sudah mencapai batas %d klaim untuk "%s".', $quota, $rewardName),
            ['quota' => $quota],
        );
    }

    public static function inactive(string $rewardName): self
    {
        return new self(
            self::INACTIVE,
            sprintf('Hadiah "%s" sedang tidak tersedia.', $rewardName),
        );
    }

    public static function cannotCancel(string $status): self
    {
        return new self(
            self::CANNOT_CANCEL,
            sprintf('Klaim berstatus "%s" tidak bisa dibatalkan.', $status),
            ['status' => $status],
        );
    }

    /**
     * Status HTTP yang pantas untuk tiap alasan. 409 untuk konflik keadaan
     * (stok/kuota), 402 untuk saldo kurang, 404/422 untuk sisanya.
     */
    public function httpStatus(): int
    {
        return match ($this->reason) {
            self::INSUFFICIENT_COINS => 402,
            self::OUT_OF_STOCK, self::QUOTA_EXCEEDED => 409,
            self::INACTIVE, self::CANNOT_CANCEL => 422,
            default => 400,
        };
    }
}