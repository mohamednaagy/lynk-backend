<?php

namespace App\Http\Controllers\Api\V1\Trader\Auth;

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
        return fractal($request->user()->load(['roles']), new UserTransformer(Area::Trader))
            ->parseIncludes([
                'id',
                'first_name',
                'last_name',
                'email',
                'is_email_verified',
                'role',
                'company.id',
                'company.name',
                'company.status',
                'company.public_status_comment',
                'permissions',
                'locale',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
            ])->respond();
    }
}
