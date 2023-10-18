<?php

namespace App\Actions\Edaat;

use App\Actions\Contracts\Edaat\CreateEdaatInvoice;
use App\Enums\EdaatInvoiceStatus;
use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;
use Cknow\Money\Money;

class CreateEdaatInvoiceAction implements CreateEdaatInvoice
{
    public function __construct(
        protected EdaatService $edaatService
    ) {
    }

    public function handle(Money $amount): EdaatInvoice
    {
        $invoice = EdaatInvoice::create([
            'amount' => $amount,
            'status' => EdaatInvoiceStatus::Pending,
            'creator_id' => auth()->user()->getAuthIdentifier(),
        ]);

        $invoice->creator()->associate(auth()->user());

        $invoiceNumber = $this->edaatService->createInvoice($invoice->id, $amount->convertAndFormatByDecimal());

        $invoice->update([
            'invoice_number' => $invoiceNumber,
        ]);

        return $invoice;
    }
}
