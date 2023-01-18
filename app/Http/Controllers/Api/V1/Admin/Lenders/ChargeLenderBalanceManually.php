<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Companies\ChargeLenderBalanceManually as ChargeLenderBalanceManuallyInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\StoreTransactionRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ChargeLenderBalanceManually extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Manage, Action::Charge])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  StoreTransactionRequest  $request
     * @param  Company  $lender
     * @param  ChargeLenderBalanceManuallyInterface  $chargeBalanceManuallyInterface
     * @return JsonResponse
     */
    public function __invoke(
        StoreTransactionRequest $request,
        Company $lender,
        ChargeLenderBalanceManuallyInterface $chargeBalanceManuallyInterface
    ): JsonResponse {
        return DB::transaction(function () use ($request, $lender, $chargeBalanceManuallyInterface) {
            $chargeBalanceManuallyInterface->handle($lender, $request->validated());

            return $this->successResponse();
        });
    }
}
