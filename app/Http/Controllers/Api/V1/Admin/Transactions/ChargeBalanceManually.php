<?php

namespace App\Http\Controllers\Api\V1\Admin\Transactions;

use App\Actions\Contracts\Admins\Transactions\ChargeBalanceManually as ChargeBalanceManuallyInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Transactions\StoreTransactionRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

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
        $chargeBalanceManuallyInterface->handle($company, $storeTransactionRequest->validated());

        return $this->successResponse();
    }
}
