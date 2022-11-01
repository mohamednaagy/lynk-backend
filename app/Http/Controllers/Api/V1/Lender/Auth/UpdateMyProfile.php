<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\UpdateMyProfileRequest;
use Illuminate\Support\Arr;

class UpdateMyProfile extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  UpdateUser  $updateUser
     * @param  UpdateLenderRequest  $updateLenderRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateUser $updateUser, UpdateMyProfileRequest $updateLenderRequest)
    {
        $vaLidated = $updateLenderRequest->validated();
        if (array_key_exists('password', $vaLidated) && is_null($vaLidated['password'])) {
            $vaLidated = Arr::except($vaLidated, 'password');
        }

        $updateUser->handle(auth()->user(), $vaLidated);

        return $this->successResponse();
    }
}
