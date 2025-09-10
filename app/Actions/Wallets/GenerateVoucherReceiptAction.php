<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GenerateVoucherReceipt;
use App\Models\Transaction;
use App\Support\DocumentEngine\PdfFactory;
use Cknow\Money\Money;

class GenerateVoucherReceiptAction implements GenerateVoucherReceipt
{
    public function handle(Transaction $transaction, Money $amount)
    {
        $pdf = PdfFactory::make('voucher_receipt');
        $pdf->setContext([
            'transaction' => $transaction,
            'amount' => $amount,
        ]);
        $pdf->generate();
    }
}
