<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\Admins\Auth\CompleteAdminRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Auth\CompleteAdminRegisterRequest;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;

class CompleteAdminRegister extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'throttle:6,1']);
    }

    public function __invoke(
        // __REVIEW__ move FormRequest to be first argument
        // __REVIEW__ $user is wrong!!
        User $user,
        CompleteAdminRegisterRequest $completeAdminRegisterRequest,
        CompleteAdminRegistration $completeAdminRegistration
    ): JsonResponse {
        // __REVIEW__ use DB::transaction(...)
        // __REVIEW__ adding check on password. If password is not null, this means the user has completed the registration
        $user = $completeAdminRegistration->handle($user, $completeAdminRegisterRequest->validated());

        return fractal($user, new UserTransformer)->respond();
    }
}
