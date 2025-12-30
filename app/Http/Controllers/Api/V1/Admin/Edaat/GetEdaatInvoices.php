<?php

namespace App\Http\Controllers\Api\V1\Admin\Edaat;

use App\Actions\Contracts\Edaat\GetEdaatInvoices as GetEdaatInvoicesInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Edaat\EdaatInvoiceFilterRequest;
use App\Transformers\EdaatInvoiceTransformer;
use Illuminate\Http\JsonResponse;

class GetEdaatInvoices extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderEdaatInvoices, Action::Manage, Action::Index])
        );
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(EdaatInvoiceFilterRequest $request, GetEdaatInvoicesInterface $getEdaatInvoices): JsonResponse
    {
        $edaatInvoices = $getEdaatInvoices->handle()
            ->with(['lender', 'creator'])
            ->paginate();

        return fractal($edaatInvoices, new EdaatInvoiceTransformer)
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
                'created_at',
                'paid_at',
            ])
            ->respond();
    }
}
