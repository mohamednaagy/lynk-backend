<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\Auth\UpdateMyProfile as UpdateMyProfileInterface;
use App\Enums\Area;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\UpdateMyProfileRequest;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Spatie\Permission\Exceptions\UnauthorizedException;

class UpdateMyProfile extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  UpdateMyProfileRequest  $request
     * @param  UpdateMyProfileInterface  $updateMyProfile
     * @return JsonResponse
     */
    public function __invoke(UpdateMyProfileRequest $request, UpdateMyProfileInterface $updateMyProfile)
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        if ($user->hasRole(Role::LenderApiUser)) {
            throw UnauthorizedException::forRoles([Role::LenderApiUser]);
        }

        if (empty($data['password'])) {
            $data = Arr::except($data, 'password');
        }

        $updateMyProfile->handle($user, $data, Area::Lender);

        return fractal($user, new UserTransformer())
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
