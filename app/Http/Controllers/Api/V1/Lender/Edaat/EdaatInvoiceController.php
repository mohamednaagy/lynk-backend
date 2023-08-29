<?php

namespace App\Http\Controllers\Api\V1\Lender\Edaat;

use App\Actions\Contracts\Edaat\CreateEdaatInvoice as CreateEdaatInvoiceInterface;
use App\Actions\Contracts\Edaat\GetEdaatInvoices as GetEdaatInvoicesInterface;
use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\CalculateOrdersRequest;
use App\Models\Company;
use App\Models\TieredPricing;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceSortByCreatedAtScope;
use App\Transformers\EdaatInvoiceTransformer;
use Cknow\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EdaatInvoiceController extends Controller
{
    public function index(
        Request $request,
        GetEdaatInvoicesInterface $getEdaatInvoices
    ): JsonResponse {
        $edaatInvoices = $getEdaatInvoices->handle(['sort_by_created_at' => InvoiceSortByCreatedAtScope::class])
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
                'created_at',
            ])
            ->respond();
    }

    public function store(
        CalculateOrdersRequest $request,
        $calculateOrderCost,
        CreateEdaatInvoiceInterface $createEdaatInvoice
    ) {
        return DB::transaction(function () use ($createEdaatInvoice, $request) {
            /** @var Company $company */
            $company = tenant();

            $amount = $this->resolveAmount($request, $company);

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

    protected function resolveAmount(Request $request, Company $company)
    {
        $orderCostForStandrdPricing = TieredPricing::getOrderCostIfStandard($company);
        if ($orderCostForStandrdPricing) {
            return app(CalculateOrdersCost::class)->handle(
                $request->validated('orders_count'),
                $orderCostForStandrdPricing['costWithoutVat']
            );
        } else {
            return Money::parseByDecimal(
                $request->validated('amount'),
                $company->getWallet(WalletType::CompanyWallet)->currency
            );
        }
    }
}
