<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Actions\Contracts\Wallets\CreateEdaatInvoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\CalculateOrdersRequest;
use Illuminate\Http\JsonResponse;

class EdaatInvoiceController extends Controller
{
    public function __invoke(
        CalculateOrdersRequest $request,
        CalculateOrdersCost $calculateOrderCost,
        CreateEdaatInvoice $createEdaatInvoice
    ): JsonResponse {
        $amount = $calculateOrderCost->handle(
            $request->input('orders_count'),
            tenant()->order_cost
        );

        $invoice = $createEdaatInvoice->handle($amount);

        return $this->successResponse([
            'amount' => $invoice->amount,
            'invoice_number' => $invoice->invoice_number,
            'company_number' => 903,
            'company_name' => trans('global.edaat'),
        ]);
    }
}
