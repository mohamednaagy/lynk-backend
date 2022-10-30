<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateEdaatInvoice;
use App\Enums\EdaatInvoiceStatus;
use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;
use Bavix\Wallet\Models\Wallet;
use DB;

class CreateEdaatInvoiceAction implements CreateEdaatInvoice
{
    public function __construct(
        protected  EdaatService $edaatService
    ) {
    }

    public function handle(Wallet $wallet, $amount): EdaatInvoice
    {
        return DB::transaction(function () use ($wallet, $amount) {
            $invoice = new EdaatInvoice();
            $invoice->fill([
                'amount' => $amount,
                'status' => EdaatInvoiceStatus::Pending,
            ]);
            $invoice->creator()->associate(auth()->user());
            $invoice->wallet()->associate($wallet);
            $invoice->save();

            $invoiceNumber = $this->edaatService->createInvoice($invoice->id, $amount);

            $invoice->fill([
                'invoice_number' => $invoiceNumber,
            ])->save();

            return $invoice;
        });
    }
}
