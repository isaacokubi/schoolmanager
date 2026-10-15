<?php

// PRESENTATION NOTE: console.
// This file defines application routes or automated tests. The comments explain
// the purpose of the route/test setup; they do not change request handling or assertions.

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Register a console command or scheduled task for command-line execution.
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
