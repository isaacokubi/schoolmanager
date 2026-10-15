<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
/**
 * Defines the kernel component of the school-management application.
 *
 * This file contains application logic, framework configuration, or schema
 * changes. The method comments describe each operation's purpose and lifecycle.
 */

class Kernel extends ConsoleKernel
{
    protected $commands = [];

    /**
     * Helper method for Kernel.php; it performs the schedule step and returns or updates the data needed by its caller.
     */
    protected function schedule(Schedule $schedule)
    {
        // Scheduled tasks may be registered here.
    }

    /**
     * Helper method for Kernel.php; it performs the commands step and returns or updates the data needed by its caller.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
