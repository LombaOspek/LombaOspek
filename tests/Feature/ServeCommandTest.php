<?php

namespace Tests\Feature;

use App\Console\Commands\ServeCommand;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Regresi untuk crash `php artisan serve` di Windows:
 * ErrorException "Undefined array key 1" saat baris log server PHP tiba
 * tanpa prefiks tanggal.
 */
class ServeCommandTest extends TestCase
{
    private function command(): ServeCommand
    {
        return new ServeCommand($this->app['events']);
    }

    private function callGetDate(string $line): Carbon
    {
        $method = new \ReflectionMethod(ServeCommand::class, 'getDateFromLine');
        $method->setAccessible(true);

        return $method->invoke($this->command(), $line);
    }

    public function test_line_without_timestamp_does_not_throw(): void
    {
        // Baris inilah yang dulu mematikan perintah serve.
        $date = $this->callGetDate('127.0.0.1:62811 Accepted');

        $this->assertInstanceOf(Carbon::class, $date);
    }

    public function test_line_with_timestamp_is_parsed(): void
    {
        $date = $this->callGetDate('[Sun Nov 01 14:30:36 2026] 127.0.0.1:62811 Accepted');

        $this->assertSame('2026-11-01 14:30:36', $date->format('Y-m-d H:i:s'));
    }

    public function test_malformed_timestamp_falls_back_to_now(): void
    {
        // Prefiks ada tapi isinya bukan tanggal valid: tetap tidak boleh meledak.
        $date = $this->callGetDate('[not a date] 127.0.0.1:1 Accepted');

        $this->assertInstanceOf(Carbon::class, $date);
    }

    public function test_empty_line_does_not_throw(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->callGetDate(''));
    }
}
