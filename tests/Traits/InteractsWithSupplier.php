<?php

namespace Tests\Traits;

use App\Enums\CommoitySupplierStatus;
use App\Enums\CompanyType;
use App\Models\Supplier;

trait InteractsWithSupplier
{
    public function createSupplier(
        array $data = [], $commodityStatus = CommoitySupplierStatus::Active
    ): Supplier {
        $data['type'] = CompanyType::Supplier;
        $supplier = Supplier::factory()->create($data);
        $supplier->commoditySupplier()->create(['desc' => 'test desc', 'status' => $commodityStatus]);

        return $supplier;
    }
}
