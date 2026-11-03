<?php

namespace App\Console\Commands;

use App\Models\CoinTransaction;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\CoinService;
use Illuminate\Console\Command;

/**
 * Backfill koin untuk mahasiswa yang XP-nya diperoleh SEBELUM koin ada.
 *
 * Kenapa perlu: fitur koin ditambahkan setelah sebagian mahasiswa sudah
 * mengumpulkan XP. Tanpa backfill, 1007 mahasiswa bersaldo 0 dan Bursa Hadiah
 * menampilkan "Koin Kurang" untuk semuanya.
 *
 * Cara kerjanya: membaca ledger XP (bukan kolom users.xp) lalu mengubah tiap
 * mutasi menjadi koin memakai rasio yang sama seperti CoinService. Memakai
 * ledger penting — kolom users.xp bisa saja sudah tidak sinkron, sedangkan
 * ledger mencatat asal-usul tiap poin.
 *
 * Sumber yang TIDAK di-backfill: `admin_grant` dan `reward_refund`. Keduanya
 * bukan aktivitas mahasiswa.
 *
 * Idempoten: memakai idempotency_key `backfill:{xp_transaction_id}` sehingga
 * dijalankan dua kali tidak menggandakan saldo.
 */
class BackfillCoinsCommand extends Command
{
    protected $signature = 'coins:backfill
                            {--dry-run : Hitung saja, jangan tulis ke database}
                            {--nim= : Batasi ke satu NIM saja}';

    protected $description = 'Isi koin mahasiswa dari riwayat XP yang sudah ada sebelumnya.';

    /** Sumber yang dihitung sebagai aktivitas mahasiswa. */
    private const SOURCES = ['quest', 'quiz', 'side_quest', 'achievement', 'daily_box', 'secret_mission'];

    public function handle(CoinService $coins): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = User::query()
            ->where('role', 'student')
            ->when($this->option('nim'), fn ($q, $nim) => $q->where('nim', $nim))
            ->orderBy('id');

        $totalStudents = $query->count();

        if ($totalStudents === 0) {
            $this->warn('Tidak ada mahasiswa yang cocok.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Memproses {$totalStudents} mahasiswa...");

        $bar = $this->output->createProgressBar($totalStudents);
        $bar->start();

        $usersTouched = 0;
        $coinsGranted = 0;
        $entries = 0;

        $query->chunk(100, function ($students) use ($coins, $dryRun, &$usersTouched, &$coinsGranted, &$entries, $bar): void {
            foreach ($students as $student) {
                $rows = XpTransaction::query()
                    ->where('user_id', $student->getKey())
                    ->where('amount', '>', 0)
                    ->whereIn('source_type', self::SOURCES)
                    ->orderBy('id')
                    ->get();

                $perStudent = 0;

                foreach ($rows as $row) {
                    // Jenis sumber ditentukan dari deskripsi untuk modul:
                    // XP lama tidak membedakan side quest dari modul biasa,
                    // jadi modul diperlakukan sebagai 'quest' (10%). Mahasiswa
                    // tetap mendapat koin Side Quest penuh untuk aktivitas
                    // setelah fitur ini aktif.
                    $type = $this->normalizeSource($row->source_type);

                    $amount = $coins->coinsFor((int) $row->amount, $type);

                    if ($amount <= 0) {
                        continue;
                    }

                    $perStudent += $amount;
                    $entries++;

                    if ($dryRun) {
                        continue;
                    }

                    CoinTransaction::create([
                        'user_id' => $student->getKey(),
                        'amount' => $amount,
                        'balance_after' => 0, // dikoreksi setelah semua baris ditulis
                        'source_type' => $row->source_type,
                        'source_id' => $row->source_id,
                        'description' => 'Koin dari riwayat: '.$row->description,
                        'idempotency_key' => 'backfill:'.$row->getKey(),
                    ]);
                }

                if ($perStudent > 0) {
                    $usersTouched++;
                    $coinsGranted += $perStudent;
                }

                if (! $dryRun && $perStudent > 0) {
                    $fresh = User::query()->lockForUpdate()->find($student->getKey());

                    if ($fresh !== null) {
                        $fresh->coins = (int) CoinTransaction::query()
                            ->where('user_id', $fresh->getKey())
                            ->sum('amount');
                        $fresh->save();
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Rapikan balance_after agar konsisten dengan saldo akhir. Tanpa ini
        // setiap baris menulis 0 dan audit rekonsiliasi gagal.
        if (! $dryRun && $entries > 0) {
            $this->info('Menyelaraskan balance_after...');

            User::query()
                ->where('role', 'student')
                ->when($this->option('nim'), fn ($q, $nim) => $q->where('nim', $nim))
                ->chunk(200, function ($students): void {
                    foreach ($students as $student) {
                        $running = 0;

                        $rows = CoinTransaction::query()
                            ->where('user_id', $student->getKey())
                            ->orderBy('id')
                            ->get();

                        foreach ($rows as $row) {
                            $running += (int) $row->amount;
                            $row->forceFill(['balance_after' => $running])->save();
                        }
                    }
                });
        }

        $this->newLine();
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Mahasiswa diproses', $totalStudents],
                ['Mahasiswa menerima koin', $usersTouched],
                ['Baris ledger dibuat', $entries],
                ['Total koin diberikan', number_format($coinsGranted, 0, ',', '.')],
                ['Mode', $dryRun ? 'DRY RUN (tidak menulis)' : 'TULIS'],
            ],
        );

        if ($dryRun) {
            $this->warn('Jalankan tanpa --dry-run untuk menerapkan.');
        }

        return self::SUCCESS;
    }

    /**
     * Ledger lama hanya mengenal 'quest'. Side quest baru ada belakangan,
     * jadi pemetaannya sudah benar apa adanya.
     */
    private function normalizeSource(string $sourceType): string
    {
        return $sourceType === 'quest' ? 'quest' : $sourceType;
    }
}
