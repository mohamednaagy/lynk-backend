<?php

namespace App\Http\Controllers\Api\V1\Trader\Auth;

use App\Actions\Contracts\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Auth\UpdateMyProfileRequest;

class UpdateMyProfile extends Controller
{
    /**
     * Summary of __invoke
     *
     * @param  UpdateMyProfileRequest  $request
     * @param  UpdateUser  $updateUser
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(
        UpdateMyProfileRequest $request,
        UpdateUser $updateUser
    ) {
        $updateUser->handle($request->user(), $request->validated());

        return $this->successResponse();
    }
}
