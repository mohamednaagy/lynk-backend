<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\Lenders\Auth\CompleteUserRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\CompleteRegisterRequest;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;

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
        // __REVIEW__ Move request to be first before $user
        User $user,
        CompleteRegisterRequest $request,
        // __REIVEW__ $CompleteUserRegistration -> $completeUserRegistration
        CompleteUserRegistration $CompleteUserRegistration
    ): JsonResponse {
        // __REVIEW__ use DB::transaction();
        $user = $CompleteUserRegistration->handle($user, $request->validated());

        // __REVIEW__ return $this->successResponse();
        return fractal($user, new UserTransformer)->respond();
    }
}
