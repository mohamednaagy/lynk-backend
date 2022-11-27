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
        int $invoice
    ): JsonResponse {
        // According to https://bavix.github.io/laravel-wallet/#/transaction,
        // DB::transaction() not working with Wallet version below 9.6, and we are using 9.5
        app(DatabaseServiceInterface::class)->transaction(static function () use (
            $checkEdaatInvoiceStatus,
            $invoice
        ) {
            $invoice = EdaatInvoice::lockForUpdate()->findOrFail($invoice);
            $checkEdaatInvoiceStatus->handle($invoice);
        });

        return $this->successResponse();
    }
}
