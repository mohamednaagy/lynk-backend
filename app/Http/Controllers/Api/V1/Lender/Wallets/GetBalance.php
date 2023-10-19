<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetBalance extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::LenderWallet, Action::Show, Action::Manage])
        );
    }

    public function __invoke(Request $request, GetLenderBalance $getBalance): JsonResponse
    {
        $company = tenant();
        $balances = $getBalance->handle(tenant());

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
