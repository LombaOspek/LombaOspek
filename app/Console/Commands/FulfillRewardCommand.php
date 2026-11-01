<?php

namespace App\Console\Commands;

use App\Models\RewardRedemption;
use App\Services\RewardService;
use Illuminate\Console\Command;

/**
 * Penyerahan hadiah di meja panitia.
 *
 * Panitia memindai / mengetik kode klaim, lalu menandainya sudah diambil.
 * Tanpa langkah ini, tidak ada cara membedakan klaim yang sudah diserahkan
 * dari yang belum — dan mahasiswa bisa menagih dua kali dengan kode sama.
 */
class FulfillRewardCommand extends Command
{
    protected $signature = 'reward:fulfill
                            {code : Kode klaim, mis. OQ-AB12-CD34}
                            {--cancel : Batalkan klaim dan kembalikan koin + stok}
                            {--reason= : Alasan pembatalan (wajib bila --cancel)}';

    protected $description = 'Tandai klaim hadiah sudah diserahkan, atau batalkan klaimnya.';

    public function handle(RewardService $rewards): int
    {
        $code = strtoupper(trim((string) $this->argument('code')));

        $redemption = RewardRedemption::query()
            ->with(['reward', 'user'])
            ->where('claim_code', $code)
            ->first();

        if ($redemption === null) {
            $this->error("Kode klaim tidak ditemukan: {$code}");

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Klaim: %s · %s · %s koin · status %s',
            $redemption->claim_code,
            $redemption->reward?->name ?? '(hadiah dihapus)',
            number_format($redemption->coins_spent, 0, ',', '.'),
            $redemption->status,
        ));
        $this->line(sprintf(
            'Mahasiswa: %s (%s)',
            $redemption->user?->name ?? '(akun dihapus)',
            $redemption->user?->nim ?? '-',
        ));

        if ($this->option('cancel')) {
            return $this->cancel($rewards, $redemption);
        }

        if (! $redemption->isPending()) {
            $this->warn("Statusnya sudah \"{$redemption->status}\" — tidak ada yang perlu diserahkan.");

            return self::SUCCESS;
        }

        $redemption->forceFill([
            'status' => RewardRedemption::STATUS_FULFILLED,
            'fulfilled_at' => now(),
        ])->save();

        $this->info("Ditandai sudah diambil pada {$redemption->fulfilled_at->format('d M Y H:i')}.");

        return self::SUCCESS;
    }

    private function cancel(RewardService $rewards, RewardRedemption $redemption): int
    {
        $reason = trim((string) $this->option('reason'));

        if ($reason === '') {
            $this->error('Opsi --reason wajib diisi supaya alasan pembatalan tercatat.');

            return self::FAILURE;
        }

        try {
            $rewards->cancelRedemption($redemption, $reason);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Klaim dibatalkan. %s koin dikembalikan dan stok dipulihkan.',
            number_format($redemption->fresh()->coins_spent, 0, ',', '.'),
        ));

        return self::SUCCESS;
    }
}