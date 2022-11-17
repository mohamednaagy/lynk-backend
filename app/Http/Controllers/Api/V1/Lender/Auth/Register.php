<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\Lenders\Auth\RegisterLender;
use App\Actions\Contracts\LoginUser;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\RegisterLenderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class Register extends Controller
{
    public function __invoke(
        RegisterLenderRequest $request,
        RegisterLender $registerLender,
        LoginUser $loginUser,
        GetSettingsClassInstance $getSettingsClassInstance
    ): JsonResponse {
        return DB::transaction(function () use ($loginUser, $request, $registerLender, $getSettingsClassInstance) {
            // __REVIEW__ add default_does_order_require_approval in
            // the Lender setting and pass it in the array_merge as "does_order_require_approval"
            $data = array_merge(
                $request->validated(),
                [
                    'company_status' => $getSettingsClassInstance->handle(Area::Lender)->company_registration_status,
                    'order_cost' => $getSettingsClassInstance->handle(Area::Lender)->order_cost,
                ]
            );

            $lender = $registerLender->handle($data);

            return $this->successResponse(
                $loginUser->handle($lender, $request->source, $request),
                Response::HTTP_CREATED
            );
        });
    }
}
