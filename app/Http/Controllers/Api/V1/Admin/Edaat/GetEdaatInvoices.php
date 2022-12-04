<?php

namespace App\Http\Controllers\Api\V1\Admin\Edaat;

use App\Actions\Contracts\Edaat\GetEdaatInvoices as GetEdaatInvoicesInterface;
use App\Http\Controllers\Controller;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceCompanyScope;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceNumberScope;
use App\Transformers\EdaatInvoiceTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetEdaatInvoices extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  GetEdaatInvoicesInterface  $getEdaatInvoices
     * @return JsonResponse
     */
    public function __invoke(Request $request, GetEdaatInvoicesInterface $getEdaatInvoices): JsonResponse
    {
        $edaatInvoices = $getEdaatInvoices->handle($this->scopes())
            ->with(['company', 'creator'])
            ->paginate();

        return fractal($edaatInvoices, new EdaatInvoiceTransformer())
            ->parseIncludes([
                'id',
                'invoice_number',
                'amount',
                'amount_formatted',
                'creator',
                'company_name',
                'company_number',
                'status',
                'company',
            ])
            ->respond();
    }

    public function scopes(): array
    {
        return [
            'invoice_number' => InvoiceNumberScope::class,
            'company_id' => InvoiceCompanyScope::class,
        ];
    }
}
