<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\CheckTraderOrderSettlement;
use App\Enums\TraderOrderSettlementStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Clients\LynkClient;
use Exception;
use Illuminate\Support\Facades\Log;

class CheckTraderOrderSettlementAction implements CheckTraderOrderSettlement
{
    /**
     * Check if a trader order's commodities are settled.
     *
     * This action handles the complete settlement check flow:
     * - Calls LocalMarket to check settlement status
     * - Creates or updates settlement record with the result
     * - Handles errors and creates failed settlement records
     */
    public function handle(TraderOrder $traderOrder): void
    {
        // Return early if already settled
        if ($traderOrder->isCommoditiesSettled()) {
            return;
        }

        // Mark as in progress
        $traderOrder->latestSettlement()->update([
            'status' => TraderOrderSettlementStatus::InProgress,
        ]);

        try {
            // Check settlement via LocalMarket
            $isSettled = LynkClient::of($traderOrder)->checkOrderSettlement();

            // Update latest settlement record with result
            $traderOrder->latestSettlement()->update([
                'is_commodities_settled' => $isSettled,
                'status' => TraderOrderSettlementStatus::Completed,
            ]);

        } catch (Exception $e) {
            // Update settlement record as failed
            $traderOrder->latestSettlement()->update([
                'is_commodities_settled' => false,
                'status' => TraderOrderSettlementStatus::Failed,
            ]);

            Log::channel(LOG_CHANNEL_LYNK)->error(
                formatLogTitle('Error while checking trader order settlement', $traderOrder),
                [
                    'trader_order_id' => $traderOrder->id,
                    'reference' => $traderOrder->reference,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            throw $e;
        }
    }
}
