<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\Lenders\Auth\CompleteUserRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\CompleteRegisterRequest;
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
        User $user,
        CompleteRegisterRequest $completeRegisterRequest,
        CompleteUserRegistration $CompleteUserRegistration
    ): JsonResponse {
        $user = $CompleteUserRegistration->handle($user, $completeRegisterRequest->validated());

        return fractal($user, new UserTransformer)->respond();
    }
}
