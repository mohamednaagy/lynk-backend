<?php

namespace App\Http\Controllers\Api\V1\Edaat;

use App\Enums\EdaatInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __invoke(
        Request $request,
        EdaatService $edaatService,
        string $status
    ) {
        foreach ($request->all() as $invoice) {
            if ($edaatService->isPaidInvoice($invoice['InvoiceNo'])) {
                EdaatInvoice::where('id', $invoice['InternalCode'])
                    ->update(['status' => EdaatInvoiceStatus::Paid]);
            }
        }
    }
}
