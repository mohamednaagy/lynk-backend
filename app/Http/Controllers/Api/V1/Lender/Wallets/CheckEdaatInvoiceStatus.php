<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\CheckEdaatInvoiceStatus as CheckEdaatInvoiceStatusInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\EdaatInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CheckEdaatInvoiceStatus extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderEdaatInvoices, Action::Manage, Action::SyncStatusWithEdaat])
        );
    }

    public function __invoke(
        CheckEdaatInvoiceStatusInterface $checkEdaatInvoiceStatus,
        int $invoice
    ): JsonResponse {
        DB::multipleTransaction(function () use ($checkEdaatInvoiceStatus, $invoice) {
            $invoice = EdaatInvoice::lockForUpdate()->findOrFail($invoice);
            $checkEdaatInvoiceStatus->handle($invoice);
        });

        return $this->successResponse();
    }
}
