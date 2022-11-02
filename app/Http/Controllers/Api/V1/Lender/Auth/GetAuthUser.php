<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;

class GetAuthUser extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        return fractal($request->user()->load(['roles']), new UserTransformer(Area::Lender))
            ->parseIncludes(['is_email_verified', 'role', 'company', 'permissions'])
            ->respond();
    }
}
