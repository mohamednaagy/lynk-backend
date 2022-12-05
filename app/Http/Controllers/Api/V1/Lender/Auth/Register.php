<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\Lenders\Auth\RegisterLender;
use App\Actions\Contracts\LoginUser;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\RegisterLenderRequest;
use App\Jobs\Lenders\NotifyAdminsAboutLenderRegistration;
use Cknow\Money\Money;
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
        return DB::multipleTransaction(function () use ($loginUser, $request, $registerLender, $getSettingsClassInstance) {
            $data = array_merge(
                $request->validated(),
                [
                    'does_order_require_approval' => $getSettingsClassInstance->handle(Area::Lender)
                        ->default_does_order_require_approval,
                    'company_status' => $getSettingsClassInstance->handle(Area::Lender)
                        ->default_company_registration_status,
                    'order_cost' => Money::parseByDecimal(
                        $getSettingsClassInstance
                            ->handle(Area::Lender)->default_order_cost,
                        Money::getDefaultCurrency()
                    ),
                ]
            );

            $lender = $registerLender->handle($data);

            dispatch(new NotifyAdminsAboutLenderRegistration(tenant(), $data['redirect_url']));

            return $this->successResponse(
                $loginUser->handle($lender, $request->validated('source'), $request),
                Response::HTTP_CREATED
            );
        });
    }
}
