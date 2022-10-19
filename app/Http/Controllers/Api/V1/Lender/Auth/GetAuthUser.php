<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

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
        return fractal($request->user(), new UserTransformer)->respond();
    }
}
