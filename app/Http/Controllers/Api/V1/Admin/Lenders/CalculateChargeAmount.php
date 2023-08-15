<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Lenders\CalculateAmountWithVat;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class CalculateChargeAmount extends Controller
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
    public function __invoke(Company $lender, int $amount, CalculateAmountWithVat $calculateAmountWithVat): JsonResponse
    {
        [$amountWithoutVat, $orderCount] = $calculateAmountWithVat->handle($lender, $amount);

        return $this->successResponse(data: [
            'amount_without_vat' => $amountWithoutVat,
            'order_count' => $orderCount,
        ]);
    }
}
