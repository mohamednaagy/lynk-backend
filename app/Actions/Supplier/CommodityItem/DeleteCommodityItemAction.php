<?php

namespace App\Actions\Supplier\CommodityItem;

use App\Actions\Contracts\Supplier\CommodityItem\DeleteCommodityItem;
use App\Actions\Supplier\CommodityItem\Inventory\DeleteCommodityInventoryAction;
use App\Models\CommodityItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteCommodityItemAction implements DeleteCommodityItem
{
    protected $deleteCommodityInventoryAction;

    public function __construct(DeleteCommodityInventoryAction $deleteCommodityInventoryAction)
    {
        $this->deleteCommodityInventoryAction = $deleteCommodityInventoryAction;
    }

    public function handle(CommodityItem $commodityItem): CommodityItem
    {
        try {
            DB::beginTransaction();
            // Delete each related inventory and dispatch the job to delete its units
            foreach ($commodityItem->inventories as $inventory) {
                $this->deleteCommodityInventoryAction->handle($inventory);
            }

            // Delete the commodity item itself
            $commodityItem->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete commodity item and its inventories.', [
                'commodity_item_id' => $commodityItem->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $commodityItem;
    }
}
