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
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        return fractal($request->user()->load(['roles']), new UserTransformer(Area::Lender))
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
                'company.id',
                'company.public_status_comment',
                'company.is_tiered',
                'permissions',
                'locale',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
            ])->respond();
    }
}
