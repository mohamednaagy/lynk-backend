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
        User $user,
        CompleteAdminRegisterRequest $completeAdminRegisterRequest,
        CompleteAdminRegistration $completeAdminRegistration
    ): JsonResponse {
        $user = $completeAdminRegistration->handle($user, $completeAdminRegisterRequest->validated());

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
