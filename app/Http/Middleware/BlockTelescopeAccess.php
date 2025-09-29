<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockTelescopeAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('telescope') || $request->is('telescope/*')) {
            abort(403, 'Access to Telescope is forbidden.');
        }

        return $next($request);
    }
}
