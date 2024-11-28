<?php

namespace App\Support\DataTransferObjects\LocalMarket;

use App\Models\LocalMarketOrder;

class OrderCommoditiesDto
{
    private LocalMarketOrder $order;

    public function __construct(LocalMarketOrder $order)
    {
        $this->order = $order;
    }

    /**
     * Static method to get inventories from the order data.
     */
    public static function getInventoriesFromOrder(LocalMarketOrder $order): array
    {
        return $order->data['inventories'] ?? [];
    }

    /**
     * Convert the DTO to an array representation.
     */
    public function toArray(): array
    {
        return [
            'inventories' => $this->order->data['inventories'] ?? [],
        ];
    }
}
