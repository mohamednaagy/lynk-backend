<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocalization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->hasHeader('X-Locale')) {
            app()->setLocale($request->header('X-Locale'));
        } elseif ($request->user() && ! is_null($request->user()->locale)) {
            $locale = $request->user()->locale;
            app()->setLocale($locale);
            $response->header('X-Locale', $locale);
        }

        return $response;
    }
}
