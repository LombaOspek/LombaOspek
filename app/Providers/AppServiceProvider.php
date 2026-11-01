<?php

namespace App\Providers;

use App\Console\Commands\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ganti `artisan serve` bawaan dengan versi yang tahan terhadap baris
        // log tanpa prefiks tanggal (penyebab ErrorException "Undefined array
        // key 1" di Windows). Lihat App\Console\Commands\ServeCommand.
        if ($this->app->runningInConsole()) {
            $this->app->extend(\Illuminate\Foundation\Console\ServeCommand::class, function ($command, $app) {
                return new ServeCommand($app['events']);
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
