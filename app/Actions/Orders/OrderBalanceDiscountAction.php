<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\OrderBalanceDiscount;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Actions\Contracts\Wallets\DeductVatPercentage;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Models\FinancingOrder;

class OrderBalanceDiscountAction implements OrderBalanceDiscount
{
    public function handle(FinancingOrder $financingOrder): void
    {
        // deduct the cost from the wallet
        $creationFeeTransaction = app(DeductOrderCreationFee::class)->handle($financingOrder);
        app(DeductVatPercentage::class)->handle(
            $financingOrder,
            $creationFeeTransaction,
            $financingOrder->company
        );

        app(GenerateZatcaInvoice::class)->handel(
            $financingOrder,
            creationFeeTransaction: $creationFeeTransaction
        );
    }
}
