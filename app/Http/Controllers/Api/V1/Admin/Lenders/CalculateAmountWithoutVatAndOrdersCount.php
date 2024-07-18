<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Lenders\CalcAmountWithoutVatAndOrdersCount;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Cknow\Money\Money;
use Illuminate\Http\JsonResponse;

class CalculateAmountWithoutVatAndOrdersCount extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Company $lender,
        float $amountWithVat,
        CalcAmountWithoutVatAndOrdersCount $calcHandler
    ): JsonResponse {
        $wallet = $lender->getWallet(WalletType::CompanyWallet);
        $chargeAmountWithVatMoney = Money::parseByDecimal($amountWithVat, $wallet->currency);

        [$amountWithoutVat, $orderCount] = $calcHandler->handle($lender, $chargeAmountWithVatMoney);

        return $this->successResponse(data: [
            'amount_without_vat' => $amountWithoutVat->convertAndFormatByDecimal(),
            'amount_without_vat_formatted' => $amountWithoutVat->convertAndFormatByDecimal(sperator: ','),
            'order_count' => $orderCount,
        ]);
    }
}
