<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

final class ThrottleRequestsUnlessProduction
{
    public function handle(Request $request, Closure $next)
    {
        if (!app()->isProduction()) {
            return $next($request);
        }

        return app(ThrottleRequests::class)->handle($request, $next, 'api');
    }
}
