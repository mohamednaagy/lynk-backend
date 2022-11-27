<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\CheckEdaatInvoiceStatus as CheckEdaatInvoiceStatusInterface;
use App\Http\Controllers\Controller;
use App\Models\EdaatInvoice;
use Bavix\Wallet\Internal\Service\DatabaseServiceInterface;
use Illuminate\Http\JsonResponse;

class CheckEdaatInvoiceStatus extends Controller
{
    public function __invoke(
        CheckEdaatInvoiceStatusInterface $checkEdaatInvoiceStatus,
        // __REVIEW__ don't load $invoice as model (see comment on line 23)
        EdaatInvoice $invoice
    ): JsonResponse {
        // __REVIEW__ use DB::transaction(...) instead of app(DatabaseServiceInterface::class)
        app(DatabaseServiceInterface::class)->transaction(static function () use (
            $checkEdaatInvoiceStatus,
            $invoice
        ) {
            // __REVIEW__ retreive $invoice from the database and lockForUpdate
            // __REVIEW__ for reference, see: app/Http/Controllers/Api/V1/Lender/Orders/RejectOrder.php
            $checkEdaatInvoiceStatus->handle($invoice);
        });

        return $this->successResponse();
    }
}
