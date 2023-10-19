<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Lenders\GetLenderBalance as GetLenderBalanceInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class GetLenderBalance extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Show, Action::Manage])
        );
    }

    public function __invoke(Company $lender, GetLenderBalanceInterface $getBalance): JsonResponse
    {
        $balances = $getBalance->handle($lender);

        return $this->successResponse(data: [
            'balance' => $balances['balance']->convertAndFormatByDecimal(),
            'balance_formatted' => $balances['balance']->convertAndFormatByDecimal(sperator: ','),
            'available_orders' => $balances['availableOrders'],
            'available_orders_formatted' => $balances['availableOrders']
                ? number_format($balances['availableOrders'])
                : null,

        ]);
    }
}
