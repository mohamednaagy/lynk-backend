<?php

namespace App\Http\Controllers\Api\V1\Lender\Edaat;

use App\Actions\Contracts\Lenders\GetEdaatInvoices as GetEdaatInvoicesInterface;
use App\Http\Controllers\Controller;
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
        $edaatInvoices = $getEdaatInvoices->handle()
            ->with('creator')
            ->paginate();

        return fractal($edaatInvoices, new EdaatInvoiceTransformer())->respond();
    }
}
