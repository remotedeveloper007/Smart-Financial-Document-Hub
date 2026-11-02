<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CleanupUploadSessions::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // cleanup upload sessions daily
        $schedule->command('uploads:cleanup --hours=24')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
