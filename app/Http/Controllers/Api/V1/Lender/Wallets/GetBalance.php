<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetBalance extends Controller
{
    public function __invoke(Request $request, GetLenderBalance $getBalance): JsonResponse
    {
        $balances = $getBalance->handle(tenant());

        return $this->successResponse(data: [
            'balance' => number_format($getBalance['balance'], 2),
            'available_orders' => number_format($getBalance['availableOrders'], 2),
        ]);
    }
}
