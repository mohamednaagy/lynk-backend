<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\Admins\Auth\CompleteAdminRegistration;
use App\Actions\Contracts\LoginUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Auth\CompleteAdminRegisterRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompleteAdminRegister extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'guest', 'throttle:6,1']);
    }

    /**
     * Handle the incoming request.
     *
     * @param  CompleteAdminRegisterRequest  $request
     * @param  User  $admin
     * @param  CompleteAdminRegistration  $completeAdminRegistration
     * @param  LoginUser  $loginUser
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function __invoke(
        CompleteAdminRegisterRequest $request,
        User $admin,
        CompleteAdminRegistration $completeAdminRegistration,
        LoginUser $loginUser
    ): JsonResponse {
        if ($admin->isRegisterCompleted()) {
            throw new AuthorizationException();
        }

        return  DB::transaction(function () use ($request, $admin, $completeAdminRegistration, $loginUser) {
            $admin = $completeAdminRegistration->handle($admin, $request->validated());

            return $this->successResponse(
                $loginUser->handle($admin, $request->validated('source'), $request)
            );
        });
    }
}
