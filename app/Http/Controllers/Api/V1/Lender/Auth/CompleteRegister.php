<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\Lenders\Auth\CompleteUserRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\CompleteRegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompleteRegister extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'throttle:6,1']);
    }

    /**
     * Handle the incoming request.
     *
     * @param  User  $user
     * @param  CompleteRegisterRequest  $request
     * @param  CompleteUserRegistration  $CompleteUserRegistration
     * @return JsonResponse
     */
    public function __invoke(
        CompleteRegisterRequest $request,
        User $user,
        CompleteUserRegistration $completeUserRegistration
    ): JsonResponse {
        return DB::transaction(function () use ($request, $user, $completeUserRegistration) {
            $user = $completeUserRegistration->handle($user, $request->validated());

            return  $this->successResponse();
        });
    }
}
