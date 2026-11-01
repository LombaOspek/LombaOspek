<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Illuminate\Support\Carbon;

/**
 * `php artisan serve` yang tahan terhadap keluaran server PHP yang terpotong.
 *
 * Perintah bawaan Laravel mem-parse setiap baris keluaran server PHP dengan
 * regex `^\[([^\]]+)\]` lalu langsung memakai `$matches[1]`. Di Windows,
 * keluaran server PHP (stderr digabung ke stdout, terutama saat banyak
 * koneksi bersamaan) kadang tiba tanpa prefiks tanggal:
 *
 *     127.0.0.1:62811 Accepted
 *
 * Regex-nya tidak cocok, `$matches[1]` tidak ada, dan Laravel melempar
 * ErrorException "Undefined array key 1" yang mematikan perintah serve.
 *
 * Kelas ini hanya mengubah cara tanggal dibaca: bila baris tidak memuat
 * prefiks tanggal, dipakai waktu sekarang. Baris yang tidak bisa diparse
 * dilewati, bukan dijadikan alasan berhenti.
 */
class ServeCommand extends BaseServeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getDateFromLine($line)
    {
        $regex = windows_os()
            ? '/^\[([^\]]+)\]/'
            : '/^\[([^\]]+)\]/';

        $line = str_replace('  ', ' ', (string) $line);

        // Baris tanpa prefiks tanggal tetap harus menghasilkan objek tanggal:
        // status request-nya tidak penting, yang penting perintah tidak mati.
        if (! preg_match($regex, $line, $matches) || ! isset($matches[1])) {
            return Carbon::now();
        }

        try {
            return Carbon::createFromFormat('D M d H:i:s Y', $matches[1]);
        } catch (\Throwable) {
            return Carbon::now();
        }
    }
}
