<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDataOfUserSupplier
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $tenant = tenant();
        if (! $tenant) {
            return response()->json(['message' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }
        $routeParameters = $request->route()->parameters();
        foreach ($routeParameters as $parameter) {
            if (is_object($parameter) && ! is_null($parameter->company_id)) {
                if ($parameter->company_id !== $tenant->id) {
                    return response()->json(['message' => 'Record not found'], Response::HTTP_NOT_FOUND);
                }
            }
        }

        return $next($request);

    }
}
