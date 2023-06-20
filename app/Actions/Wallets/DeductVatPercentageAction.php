<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\DeductVatPercentage;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\Transaction;

class DeductVatPercentageAction implements DeductVatPercentage
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    public function handle(TraderOrder $traderOrder, Transaction $transaction, Company $company)
    {
        $financingOrder = $traderOrder->order;
        $vatRate = $this->getProjectSettings->handle()->getVatRate();
        $vatPercentageFee = $company->order_cost->multiply($vatRate);

        return $this->createTransactions->handle(
            $company->getWallet(WalletType::CompanyWallet),
            TransactionReason::VatPercentageFee,
            $vatPercentageFee,
            [
                'financing_order_id' => $financingOrder->id,
                'trader_order_id' => $financingOrder->activeTraderOrder()->first()?->id,
                'reference_number' => $transaction->reference_number,
                'transaction_id' => $transaction->id,
                'vat_rate' => $vatRate,
            ],
            $transaction->reference_number
        );
    }
}
