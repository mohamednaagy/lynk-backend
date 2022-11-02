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
        EdaatInvoice $invoice
    ): JsonResponse {
        app(DatabaseServiceInterface::class)->transaction(static function () use (
            $checkEdaatInvoiceStatus,
            $invoice
        ) {
            $checkEdaatInvoiceStatus->handle($invoice);
        });

        return $this->successResponse();
    }
}
