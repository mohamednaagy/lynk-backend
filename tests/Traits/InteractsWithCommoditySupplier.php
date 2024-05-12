<?php

namespace Tests\Traits;

use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use App\Models\CommoditySupplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

trait InteractsWithCommoditySupplier
{
    public function getCommoditySupplier($number_of_objects = 5, $is_paginate = false): LengthAwarePaginator|Collection
    {
        for ($i = 0; $i < $number_of_objects; $i++) {
            $this->createCommoditySupplier();
        }

        $suppliers = CommoditySupplier::query();
        if ($is_paginate) {
            return $suppliers->paginate();
        }

        return $suppliers->get();

    }

    public function createCommoditySupplier(?string $legal_name = null, ?string $unique_name = null, ?string $description = null, int $status = 1, int $market_type = 1): Model|Builder
    {

        return CommoditySupplier::query()->create([
            'legal_name' => $legal_name ?? 'legal name'.rand(11, 999),
            'unique_name' => $unique_name ?? 'unique name'.rand(11, 999),
            'description' => $description ?? 'Test Description',
            'status' => $status ?? CommoitySupplierStatus::Active(),
            'market_type' => $market_Type ?? CommoitySupplierMarketType::Local(),
        ]);
    }
}
