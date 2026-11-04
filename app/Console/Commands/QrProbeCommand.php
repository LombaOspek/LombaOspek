<?php

namespace App\Console\Commands;

use App\Support\RewardQr;
use Illuminate\Console\Command;

class QrProbeCommand extends Command
{
    protected $signature = 'qr:probe {code=OQ-QRRF-Z6UT}';

    protected $description = 'Cetak SVG QR untuk kode klaim (alat uji).';

    public function handle(): int
    {
        $this->output->write(RewardQr::svg($this->argument('code'), 400));

        return self::SUCCESS;
    }
}
