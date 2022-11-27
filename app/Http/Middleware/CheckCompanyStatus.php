<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use Closure;
use Illuminate\Http\Request;

class CheckCompanyStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! tenant()->status->is(CompanyStatus::Approved)) {
            return $this->notAuthorizedResponse($request);
        }

        return $next($request);
    }

    private function notAuthorizedResponse(Request $request)
    {
        $message = __('error.company_not_active');
        if ($request->expectsJson()) {
            return response()->errorResponse($message, 403, ErrorCode::COMPANY_NOT_ACTIVE);
        }

        abort(403, $message);
    }
}
