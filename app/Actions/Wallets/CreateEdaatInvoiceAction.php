<?php

// __REIVEW__ move this action to be uder app\Actions\Edaat

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateEdaatInvoice;
use App\Enums\EdaatInvoiceStatus;
use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;
// __REVIEW__ use full namespace Illuminate\Support\Facades\DB
use DB;

class CreateEdaatInvoiceAction implements CreateEdaatInvoice
{
    public function __construct(
        protected  EdaatService $edaatService
    ) {
    }

    public function handle($amount): EdaatInvoice
    {
        // __REVIEW__ move DB::transaction to be in the controller. This will make the action more reusable
        return DB::transaction(function () use ($amount) {
            // __REVIEW__ use EdaatInvoice::create([...])
            $invoice = new EdaatInvoice();
            $invoice->fill([
                'amount' => $amount,
                'status' => EdaatInvoiceStatus::Pending,
            ]);
            $invoice->creator()->associate(auth()->user());
            $invoice->save();

            // __REVIEW__ create invoice first from Sadad and then create EdaatInvoice this will save the number of queries
            $invoiceNumber = $this->edaatService->createInvoice($invoice->id, $amount);

            $invoice->fill([
                'invoice_number' => $invoiceNumber,
            ])->save();

            return $invoice;
        });
    }
}
