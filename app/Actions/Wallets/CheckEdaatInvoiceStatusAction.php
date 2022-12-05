<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CheckEdaatInvoiceStatus;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;
use Cknow\Money\Money;

class CheckEdaatInvoiceStatusAction implements CheckEdaatInvoiceStatus
{
    public function __construct(
        protected  EdaatService $edaatService,
        protected CreateTransactions $createTransactions
    ) {
    }

    public function handle(EdaatInvoice $edaatInvoice): void
    {
        if ($edaatInvoice->status == EdaatInvoiceStatus::Pending()) {
            if ($this->edaatService->isPaidInvoice($edaatInvoice->invoice_number)) {
                $edaatInvoice->update(['status' => EdaatInvoiceStatus::Paid]);
                $wallet = $edaatInvoice->company->getWallet(WalletType::CompanyWallet);
                $this->createTransactions->handle(
                    $wallet,
                    TransactionReason::DepositByEdaat,
                    Money::parseByDecimal($edaatInvoice->amount, $wallet->currency),
                    [
                        'invoice_number' => $edaatInvoice->invoice_number,
                    ]
                );
            }
        }
    }
}
