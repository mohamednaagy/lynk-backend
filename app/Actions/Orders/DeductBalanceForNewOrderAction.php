<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\DeductBalanceForNewOrder;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Actions\Contracts\Wallets\DeductVatPercentage;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Models\TraderOrder;

class DeductBalanceForNewOrderAction implements DeductBalanceForNewOrder
{
    public function handle(TraderOrder $traderOrder): void
    {
        // deduct the cost from the wallet
        $creationFeeTransaction = app(DeductOrderCreationFee::class)->handle($traderOrder);

        app(DeductVatPercentage::class)->handle(
            $traderOrder,
            $creationFeeTransaction,
            $traderOrder->company()->withTrashed()->first()
        );

        app(GenerateZatcaInvoice::class)->handel(
            $traderOrder,
            creationFeeTransaction: $creationFeeTransaction
        );
    }
}
