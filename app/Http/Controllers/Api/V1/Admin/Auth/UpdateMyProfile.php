<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Auth\UpdateMyProfileRequest;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class UpdateMyProfile extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  UpdateMyProfileRequest  $updateMyProfileRequest
     * @param  UpdateUser  $updateUser
     * @return JsonResponse
     */
    public function __invoke(
        UpdateMyProfileRequest $updateMyProfileRequest,
        UpdateUser $updateUser
    ): JsonResponse {
        $user = $updateMyProfileRequest->user();
        $data = $updateMyProfileRequest->validated();

        if (array_key_exists('password', $data) && is_null($data['password'])) {
            $data = Arr::except($data, 'password');
        }

        $updateUser->handle($user, $data);

        return fractal($user, new UserTransformer)
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
