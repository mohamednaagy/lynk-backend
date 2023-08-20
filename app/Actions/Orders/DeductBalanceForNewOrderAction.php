<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\DeductBalanceForNewOrder;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Actions\Contracts\Wallets\DeductTraderOrderVatPercentage;
use App\Actions\Contracts\Wallets\GenerateTraderOrderInvoice;
use App\Models\TraderOrder;

class DeductBalanceForNewOrderAction implements DeductBalanceForNewOrder
{
    public function __construct(
        protected DeductOrderCreationFee $deductOrderCreationFee,
        protected DeductTraderOrderVatPercentage $deductVatPercentage,
        protected GenerateTraderOrderInvoice $generateZatcaInvoice
    ) {
    }

    public function handle(TraderOrder $traderOrder): void
    {
        $financingOrder = $traderOrder->order;

        // deduct the cost from the wallet
        $creationFeeTransaction = $this->deductOrderCreationFee->handle($traderOrder);

        $this->deductVatPercentage->handle(
            $traderOrder,
            $creationFeeTransaction,
            $financingOrder->company()->withTrashed()->first()
        );

        $this->generateZatcaInvoice->handle(
            $traderOrder,
            creationFeeTransaction: $creationFeeTransaction
        );
    }
}
