<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\TraderOrder;

class DeductOrderCreationFeeAction implements DeductOrderCreationFee
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    public function handle(TraderOrder $traderOrder)
    {
        $financingOrder = $traderOrder->order;
        $company = $financingOrder->company()->withTrashed()->first();
        $wallet = $company->getWallet(WalletType::CompanyWallet);
        $vatRate = $this->getProjectSettings->handle()->getVatRate();
        $vatPercentageFee = $company->order_cost->multiply($vatRate);

        return $this->createTransactions->handle(
            $wallet,
            TransactionReason::OrderCreationFee,
            $company->order_cost->add($vatPercentageFee),
            [
                'financing_order_id' => $financingOrder->id,
                'trader_order_id' => $traderOrder->id,
                'reference_number ' => $financingOrder->reference_number,
                'amount' => $financingOrder->amount,
                'order_cost' => $company->order_cost,
            ]
        );
    }
}
