<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Daily balance reconciliation at 2 AM
        $schedule->command('wallet:reconcile-balances')
                 ->dailyAt('02:00')
                 ->onOneServer()
                 ->withoutOverlapping(60); // 60 minute lock

        // Monthly transaction archiving on 1st of month at 3 AM
        $schedule->command('wallet:archive-transactions')
                 ->monthlyOn(1, '03:00')
                 ->onOneServer()
                 ->withoutOverlapping(120); // 120 minute lock

        // Queue worker monitoring (optional)
        $schedule->command('queue:work --stop-when-empty')
                 ->everyMinute()
                 ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}