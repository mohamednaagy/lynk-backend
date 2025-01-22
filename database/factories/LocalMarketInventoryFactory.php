<?php

namespace Database\Factories;

use App\Enums\LocalMarket\InventoryStatus as LocalMarketInventoryStatus;
use App\Models\CommodityItem;
use App\Models\Supplier;
use App\Models\SupplierLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocalMarketInventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => Supplier::factory(), // Assumes a Supplier factory exists
            'commodity_item_id' => CommodityItem::factory(), // Assumes a CommodityItem factory exists
            'commodity_type_id' => function (array $attributes) {
                return CommodityItem::find($attributes['commodity_item_id'])->commodity_type_id;
            },
            'supplier_location_id' => SupplierLocation::factory(), // Assumes a SupplierLocation factory exists
            'min_price' => $this->faker->randomFloat(2, 10, 100),
            'max_price' => $this->faker->randomFloat(2, 101, 500),
            'reserved_items' => $this->faker->numberBetween(0, 50),
            'available_quantity' => $this->faker->numberBetween(50, 1000),
            'status' => $this->faker->randomElement([
                LocalMarketInventoryStatus::Pending,
                LocalMarketInventoryStatus::Active,
                LocalMarketInventoryStatus::Inactive,
            ]),
        ];
    }
}
