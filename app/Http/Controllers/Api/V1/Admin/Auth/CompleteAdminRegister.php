<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\Admins\Auth\CompleteAdminRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Auth\CompleteAdminRegisterRequest;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompleteAdminRegister extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'throttle:6,1']);
    }

    public function __invoke(
        CompleteAdminRegisterRequest $completeAdminRegisterRequest,
        User $admin,
        CompleteAdminRegistration $completeAdminRegistration
    ): JsonResponse {
        if (! is_null($admin->passowrd)) {
            return fractal($admin, new UserTransformer)->respond();
        }

        DB::transaction(function () use ($completeAdminRegistration, $admin, $completeAdminRegisterRequest) {
            $completeAdminRegistration->handle($admin, $completeAdminRegisterRequest->validated());
        });

        return fractal($admin, new UserTransformer)->respond();
    }
}
