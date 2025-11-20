<?php

namespace App\Http\Middleware;

use Closure;

class MeasureLoadTime
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        logger('Load time: ' . (microtime(true) - LARAVEL_START));

        return $response;
    }
}
