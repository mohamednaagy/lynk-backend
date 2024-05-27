<?php

namespace Tests\Traits;

use App\Enums\CommodityTypeStatus;
use App\Models\CommodityType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

trait InteractsWithCommodityType
{
    public function getCommodityType($number_of_objects = 5, $is_paginate = false): LengthAwarePaginator|Collection
    {
        for ($i = 0; $i < $number_of_objects; $i++) {
            $this->createCommodityType();
        }

        $suppliers = CommodityType::query();
        if ($is_paginate) {
            return $suppliers->paginate();
        }

        return $suppliers->get();

    }

    public function createCommodityType(?string $name = null, ?string $unique_name = null, ?string $description = null, int $status = 1): Model|Builder
    {

        return CommodityType::query()->firstOrCreate([
            'name' => $name,
            'unique_name' => $unique_name,
        ], [
            'name' => $name ?? 'name'.rand(11, 999),
            'unique_name' => $unique_name ?? 'unique name'.rand(11, 999),
            'description' => $description ?? 'Test Description',
            'status' => $status ?? CommodityTypeStatus::Active(),
        ]);
    }
}
