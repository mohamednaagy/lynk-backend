<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\ChargeBalanceManually  as ChargeBalanceManuallyInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\StoreTransactionRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ChargeBalanceManually extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(
        StoreTransactionRequest $storeTransactionRequest,
        Company $company,
        ChargeBalanceManuallyInterface $chargeBalanceManuallyInterface
    ): JsonResponse {
        return DB::transaction(function () use ($storeTransactionRequest, $company, $chargeBalanceManuallyInterface) {
            $chargeBalanceManuallyInterface->handle($company, $storeTransactionRequest->validated());

            return $this->successResponse();
        });
    }
}
