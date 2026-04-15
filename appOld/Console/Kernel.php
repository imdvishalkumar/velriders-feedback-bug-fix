<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
//use App\Console\Commands\CheckPendingPayment;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CheckPendingPayment::class,
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('check:pending-payment')->everyMinute();
        $schedule->command('clear:cache-config')->dailyAt('21:00');
        //$schedule->command(CheckPendingPayment::class)->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}