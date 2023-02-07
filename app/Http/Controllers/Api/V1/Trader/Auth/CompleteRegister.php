<?php

namespace App\Http\Controllers\Api\V1\Trader\Auth;

use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\Traders\Auth\CompleteUserRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Auth\CompleteRegisterRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class CompleteRegister extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'throttle:6,1']);
    }

    /**
     * Handle the incoming request.
     *
     * @param  CompleteRegisterRequest  $request
     * @param  User  $user
     * @param  CompleteUserRegistration  $completeUserRegistration
     * @param  LoginUser  $loginUser
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function __invoke(
        CompleteRegisterRequest $request,
        User $user,
        CompleteUserRegistration $completeUserRegistration,
        LoginUser $loginUser
    ): JsonResponse {
        if ($user->isRegisterCompleted()) {
            throw new AuthorizationException();
        }

        return DB::transaction(function () use ($request, $user, $completeUserRegistration, $loginUser) {
            $user = $completeUserRegistration->handle($user, $request->validated());

            return $this->successResponse(
                $loginUser->handle($user, $request->validated('source'), $request)
            );
        });
    }
}
