<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
/**
 * Middleware component: trust proxies.
 * Middleware inspects or transforms an incoming request before it reaches the controller,
 * or applies the framework behavior associated with this request stage.
 */

class TrustProxies extends Middleware
{
    protected $proxies;
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
