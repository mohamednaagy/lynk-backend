<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\DataTransferObjects\LocalMarket\OrderCommoditiesDto;
use Exception;
use Illuminate\Support\Facades\Log;

class LoanService
{
    public function getCommoditiesForLoan(
        int $orderNo,
        int $companyId,
        float $loanAmount,
        array $preferredTypes = []
    ) {

        $inventoryService = app(InventoryService::class);
        $unitsService = app(UnitService::class);

        $eligibleInventories = $inventoryService->findEligibleInventoryForLoan(
            $loanAmount,
            $preferredTypes
        );

        if (empty($eligibleInventories)) {
            return false;
        }

        $loanDetails = $unitsService->getEligibleUnits($orderNo, $eligibleInventories);

        return $loanDetails;
    }

    public function buyCommodities(LocalMarketOrder $localMarketOrder, $companyId, $data)
    {
        $ownershipService = new OwnershipService;
        $inventoryService = new InventoryService;
        $orderService = new OrderService;

        $eligibleCommodities = OrderCommoditiesDto::fromArray($data);

        try {
            $ownershipService->changeUnitOwnership($localMarketOrder, OwnershipTypes::Company, $companyId);
            $inventoryService->refreshInventoryStocks($eligibleCommodities->getInventoriesIds());
            $orderService->insertOrderUnits($localMarketOrder);
            $orderService->insertOrderInventories($localMarketOrder, $eligibleCommodities->getInventories());
            $orderService->changeOrderStatus($localMarketOrder, LocalMarketOrderStatus::CommoditiesPurchased);

            return true;
        } catch (Exception $e) {
            Log::error('Error in buy commodities', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return false;
        }
    }

    public function sellCommodities(LocalMarketOrder $localMarketOrder)
    {

        /**
         * TODO:
         * 1- increase units rotations for the company
         * 2- change units ownershop
         * 3- remove hold for from units
         * 4- change unit status to available
         * 4- change order status to commodities sold
         * 5- log the action
         * 6- send notification to the company
         */
        Log::info('We wll sell your commodities ISA soon', ['order_id' => $localMarketOrder->id]);
    }
}
