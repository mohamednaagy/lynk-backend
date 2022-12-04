<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CheckEdaatInvoiceStatus;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;

class CheckEdaatInvoiceStatusAction implements CheckEdaatInvoiceStatus
{
    public function __construct(
        protected  EdaatService $edaatService,
        protected CreateTransactions $createTransactions
    ) {
    }

    public function handle(EdaatInvoice $edaatInvoice): void
    {
        if (
            $edaatInvoice->status === EdaatInvoiceStatus::Pending &&
            $this->edaatService->isPaidInvoice($edaatInvoice->invoice_number)
        ) {
            $edaatInvoice->update(['status' => EdaatInvoiceStatus::Paid]);
            $this->createTransactions->handle(
                $edaatInvoice->company->getWallet(WalletType::CompanyWallet),
                $edaatInvoice->amount,
                TransactionReason::DepositByEdaat,
                [
                    'invoice_number' => $edaatInvoice->invoice_number,
                ]
            );
        }
    }
}
