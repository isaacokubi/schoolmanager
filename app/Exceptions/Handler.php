<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
/**
 * Defines the handler component of the school-management application.
 *
 * This file contains application logic, framework configuration, or schema
 * changes. The method comments describe each operation's purpose and lifecycle.
 */

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register services or bindings with Laravel's service container.
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Exception reporting may be customized here.
        });
    }
}
