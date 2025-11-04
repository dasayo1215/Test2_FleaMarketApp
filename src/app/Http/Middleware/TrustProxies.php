<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * You can specify an array of proxy IPs here.
     * For ngrok/リバプロ経由なら '*' でOK。
     */
    protected $proxies = '*';

    /**
     * Forwarded headers that should be trusted.
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}