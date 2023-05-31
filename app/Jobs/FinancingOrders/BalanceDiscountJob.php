<?php

namespace App\Jobs\FinancingOrders;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Actions\Contracts\Wallets\DeductVatPercentage;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BalanceDiscountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        DeductOrderCreationFee $deductOrderCreationFee,
        DeductVatPercentage $deductVatPercentage,
        GenerateZatcaInvoice $generateFatoura,
        CanCreateOrder $canCreateOrder,
    ) {

        // deduct the cost from the wallet
        $creationFeeTransaction = $deductOrderCreationFee->handle($this->financingOrder);
        $deductVatPercentage->handle($this->financingOrder, $creationFeeTransaction, $this->financingOrder->company);

        $generateFatoura->handel(
            $this->financingOrder,
            creationFeeTransaction: $creationFeeTransaction
        );
    }
}
