<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAuthUser extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function __invoke(Request $request)
    {
        return fractal($request->user(), new UserTransformer)
            ->parseIncludes([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
            ])->respond();
    }
}
