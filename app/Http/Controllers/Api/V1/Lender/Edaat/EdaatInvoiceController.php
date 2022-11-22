<?php

namespace App\Http\Controllers\Api\V1\Lender\Edaat;

use App\Actions\Contracts\Edaat\CreateEdaatInvoice as CreateEdaatInvoiceInterface;
use App\Actions\Contracts\Edaat\GetEdaatInvoices as GetEdaatInvoicesInterface;
use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\CalculateOrdersRequest;
use App\Models\Company;
use App\Transformers\EdaatInvoiceTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EdaatInvoiceController extends Controller
{
    public function index(
        Request $request,
        GetEdaatInvoicesInterface $getEdaatInvoices
    ): JsonResponse {
        $edaatInvoices = $getEdaatInvoices->handle()
            ->with('creator')
            ->paginate();

        return fractal($edaatInvoices, new EdaatInvoiceTransformer())
            ->parseIncludes([
                'id',
                'invoice_number',
                'amount',
                'amount_formatted',
                'company_name',
                'company_number',
                'status',
            ])
            ->respond();
    }

    public function store(
        CalculateOrdersRequest $request,
        CalculateOrdersCost $calculateOrderCost,
        CreateEdaatInvoiceInterface $createEdaatInvoice
    ) {
        return DB::transaction(function () use ($createEdaatInvoice, $request, $calculateOrderCost) {
            /** @var Company $company */
            $company = tenant();

            $amount = $calculateOrderCost->handle(
                $request->validated('orders_count'),
                $company->order_cost
            );

            $invoice = $createEdaatInvoice->handle($amount);

            return fractal($invoice, new EdaatInvoiceTransformer())
                ->parseIncludes([
                    'amount',
                    'invoice_number',
                    'company_number',
                    'company_name',
                ])
                ->respond();
        });
    }
}
