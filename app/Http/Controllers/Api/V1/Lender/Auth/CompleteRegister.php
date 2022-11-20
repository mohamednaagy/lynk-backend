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
        User $user,
        CompleteRegisterRequest $request,
        CompleteUserRegistration $CompleteUserRegistration
    ): JsonResponse {
        $user = $CompleteUserRegistration->handle($user, $request->validated());

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
