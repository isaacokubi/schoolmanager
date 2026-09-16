<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Application services may be registered here.
    }

    public function boot()
    {
        Paginator::defaultView('vendor.pagination.admin');
    }
}
