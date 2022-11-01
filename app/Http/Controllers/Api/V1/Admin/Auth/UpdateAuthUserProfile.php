<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\Admins\Auth\UpdateAdminUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Auth\UpdateAdminProfileRequest;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;

class UpdateAuthUserProfile extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  UpdateAdminProfileRequest  $updateAdminProfileRequest
     * @param  UpdateAdminUser  $updateAdminUser
     * @return JsonResponse
     */
    public function __invoke(
        UpdateAdminProfileRequest $updateAdminProfileRequest,
        UpdateAdminUser $updateAdminUser
    ): JsonResponse {
        $updateAdminUser->handle($updateAdminProfileRequest->user(), $updateAdminProfileRequest->validated());

        return fractal($updateAdminProfileRequest->user(), new UserTransformer)->parseIncludes(['email'])->respond();
    }
}
