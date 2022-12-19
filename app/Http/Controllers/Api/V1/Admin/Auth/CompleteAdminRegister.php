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
        $this->middleware(['signed', 'guest', 'throttle:6,1']);
    }

    public function __invoke(
        CompleteAdminRegisterRequest $request,
        User $admin,
        CompleteAdminRegistration $completeAdminRegistration
    ): JsonResponse {
        if (! is_null($admin->passowrd)) {
            return $this->respond($admin);
        }

        DB::transaction(function () use ($completeAdminRegistration, $admin, $request) {
            $completeAdminRegistration->handle($admin, $request->validated());
        });

        return $this->respond($admin);
    }

    public function respond($admin)
    {
        return fractal($admin, new UserTransformer)
            ->parseIncludes([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
            ])
            ->respond();
    }
}
