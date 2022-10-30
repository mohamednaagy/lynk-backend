<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Actions\Contracts\Wallets\CreateEdaatInvoice as CreateEdaatInvoiceInterface;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\CalculateOrdersRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class CreateEdaatInvoice extends Controller
{
    public function __invoke(
        CalculateOrdersRequest $request,
        CalculateOrdersCost $calculateOrderCost,
        CreateEdaatInvoiceInterface $createEdaatInvoice
    ): JsonResponse {
        /** @var Company $company */
        $company = tenant();

        $amount = $calculateOrderCost->handle(
            $request->input('orders_count'),
            $company->order_cost
        );

        $wallet = $company->getWallet(WalletType::CompanyWallet);

        $invoice = $createEdaatInvoice->handle($wallet, $amount);

        return $this->successResponse([
            'amount' => $invoice->amount,
            'invoice_number' => $invoice->invoice_number,
            'company_number' => 903,
            'company_name' => trans('common.edaat'),
        ]);
    }
}
