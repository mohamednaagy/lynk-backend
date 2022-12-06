<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\Lenders\Auth\UpdateMyProfile as UpdateMyProfileInterface;
use App\Actions\Contracts\UpdateUser;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Auth\UpdateMyProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class UpdateMyProfile extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  UpdateMyProfileRequest  $request
     * @param  UpdateUser  $updateUser
     * @return JsonResponse
     */
    public function __invoke(
        UpdateMyProfileRequest $request,
        UpdateMyProfileInterface $updateMyProfile
    ): JsonResponse {
        $user = $request->user();
        $data = $request->validated();

        if (array_key_exists('password', $data) && is_null($data['password'])) {
            $data = Arr::except($data, 'password');
        }

        $updateMyProfile->handle($user, $data, Area::SuperAdmin);

        return $this->successResponse();
    }
}
