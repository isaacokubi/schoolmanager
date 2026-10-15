<?php

// PRESENTATION NOTE: CreatesApplication.
// This file defines application routes or automated tests. The comments explain
// the purpose of the route/test setup; they do not change request handling or assertions.

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Bootstrap and return the Laravel application instance used by the test suite.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
