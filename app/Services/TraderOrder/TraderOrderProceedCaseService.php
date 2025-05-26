<?php

namespace App\Services\TraderOrder;

use App\Enums\FinancingOrderProceedCase;
use App\Models\TraderOrderProceedCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TraderOrderProceedCaseService
{
    /**
     * Get the latest case for a given financing order
     *
     *
     * @throws ModelNotFoundException If trader order does not exist
     */
    public function getLatestCase(int $traderOrderId): ?FinancingOrderProceedCase
    {
        return TraderOrderProceedCase::where('trader_order_id', $traderOrderId)
            ->orderBy('id', 'desc')
            ->firstOrFail()?->case;
    }

    /**
     * Create a new case for a given financing order
     *
     * @param  int  $case  Case can be either description or enum value
     */
    public function createCase(int $traderOrderId, int $case): TraderOrderProceedCase
    {
        return TraderOrderProceedCase::create([
            'trader_order_id' => $traderOrderId,
            'case' => $case,
        ]);
    }

    /**
     * Check if a specific case exists for a given financing order
     *
     * @param  int  $case  Case can be either description or enum value
     */
    public function checkIfTraderHasCase(int $traderOrderId, int $case): bool
    {

        // Check if the case exists for the given financing order ID
        return TraderOrderProceedCase::where('trader_order_id', $traderOrderId)
            ->where('case', $case)
            ->exists();
    }

    public function getTraderCases(int $traderOrderId)
    {
        // Check if the case exists for the given financing order ID
        return TraderOrderProceedCase::where('trader_order_id', $traderOrderId)
            ->get();
    }
}
