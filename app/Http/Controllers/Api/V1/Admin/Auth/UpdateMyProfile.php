<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\Admins\UpdateAdminUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Auth\UpdateMyProfileRequest;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;

class UpdateMyProfile extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param UpdateMyProfileRequest $updateMyProfileRequest
     * @param UpdateAdminUser $updateAdminUser
     * @return JsonResponse
     */
    public function __invoke(
        UpdateMyProfileRequest $updateMyProfileRequest,
        UpdateAdminUser $updateAdminUser
    ): JsonResponse {
        $user = $updateMyProfileRequest->user();

        $updateAdminUser->handle($user, $updateMyProfileRequest->validated());

        return fractal($user, new UserTransformer)
            ->parseIncludes(['email'])
            ->respond();
    }
}
