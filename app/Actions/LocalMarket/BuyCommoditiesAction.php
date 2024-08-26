<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarketOrderStatus;
use App\Exceptions\LocalMarket\PurchaseProductException;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Support\Facades\Log;

class BuyCommoditiesAction implements BuyCommodities
{
    public function __construct(
        private LoanService $LoanService
    ) {}

    /**
     * Executes the purchase product action.
     *
     * @param  mixed  $traderOrder  The trader order details.
     * @param  array  $inventories  The inventories to be updated.
     * @return bool Indicates if the operation was successful.
     *
     * @throws PurchaseProductException If the purchase operation fails.
     */
    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        try {
            // double check if we can handle this order or not
            $eligibleCommodities = $localMarketOrder->data;

            if ($eligibleCommodities['isLoanCovered']) {
                $this->LoanService->buyCommodities($localMarketOrder, $localMarketOrder->company_id, $eligibleCommodities);
            } else {
                Log::error("Loan {$localMarketOrder->id} is not covered we can not move on ");
            }
        } catch (\Exception $e) {
            $localMarketOrder->update([
                'status' => LocalMarketOrderStatus::FailedPurchase,
            ]);

            Log::error('Error in BuyCommoditiesAction', [
                'order_id' => $localMarketOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
