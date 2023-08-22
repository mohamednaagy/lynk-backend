<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\TraderOrder;

class DeductOrderCreationFeeAction implements DeductOrderCreationFee
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected CalculateVatAmount $calculateVatAmount
    ) {
    }

    public function handle(TraderOrder $traderOrder)
    {
        $financingOrder = $traderOrder->order;
        $company = $financingOrder->company()->withTrashed()->first();
        $wallet = $company->getWallet(WalletType::CompanyWallet);
        [$vatAmount] = $this->calculateVatAmount
            ->setAmount($company->order_cost)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        return $this->createTransactions->handle(
            $wallet,
            TransactionReason::OrderCreationFee,
            $company->order_cost->add($vatAmount),
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
