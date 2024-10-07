<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Actions\Contracts\LocalMarket\PendingEligibleCommodities;
use App\Enums\LocalMarketOrderHistoryStatus;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendingEligibleCommoditiesAction implements PendingEligibleCommodities
{
    use LocalMarketHelperTrait;

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        $localMarketOrder->update(['status' => LocalMarketOrderStatus::PendingEligibleCommodities]);

        DB::beginTransaction();
        try {
            app(FindEligibleCommodities::class)->handle($localMarketOrder);
            $this->createLocalMarketOrderHistory($localMarketOrder, LocalMarketOrderHistoryStatus::PendingEligibleCommodities);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in Transactions', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $localMarketOrder->update([
                'status' => LocalMarketOrderStatus::FailedPurchase,
            ]);
        }

    }
}
