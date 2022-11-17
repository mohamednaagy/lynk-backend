<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\UpdateMyProfileRequest;
use App\Transformers\UserTransformer;
use Illuminate\Support\Arr;

class UpdateMyProfile extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  UpdateUser  $updateUser
     * @param  UpdateMyProfileRequest  $updateLenderRequest
     * @return \Illuminate\Http\JsonResponse
     */
    // __REVIEW__ : Always Request/FormRquest comes first in the arguments
    public function __invoke(UpdateUser $updateUser, UpdateMyProfileRequest $updateLenderRequest)
    {
        $validated = $updateLenderRequest->validated();

        if (array_key_exists('password', $validated) && is_null($validated['password'])) {
            $validated = Arr::except($validated, 'password');
        }

        $updateUser->handle($updateLenderRequest->user(), $validated);

        return fractal($updateLenderRequest->user(), new UserTransformer())->respond();
    }
}
