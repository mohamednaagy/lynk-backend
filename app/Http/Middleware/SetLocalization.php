<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SetLocalization
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader('X-Locale')) {
            app()->setLocale($request->header('X-Locale'));
        } elseif ($request->user() && ! is_null($request->user()->locale)) {
            $locale = $request->user()->locale;
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
