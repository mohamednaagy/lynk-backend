<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Actions\Contracts\Wallets\GetTransactions;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\CalculateOrdersRequest;
use App\Http\Requests\V1\Lender\Wallets\GetTransactionsRequest;
use App\Transformers\TransactionTransformer;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function calculateOrders(
        CalculateOrdersRequest $calculateOrdersRequest,
        CalculateOrdersCost $calculateOrdersCost
    ): JsonResponse {
        $response = $calculateOrdersCost->handle([
            'orders' => $calculateOrdersRequest->get('orders'),
            'order_cost' => auth()->user()->company->order_cost,
        ]);

        return $this->successResponse(data: [
            'amount' => $response,
        ]);
    }

    public function getTransactions(
        GetTransactionsRequest $getTransactionsRequest,
        GetTransactions $getTransactions
    ): JsonResponse {
        $response = $getTransactions->handle([]);

        return fractal($response, new TransactionTransformer())->respond();
    }
}
