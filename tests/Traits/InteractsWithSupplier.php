<?php

namespace Tests\Traits;

use App\Enums\CommoitySupplierStatus;
use App\Enums\CompanyType;
use App\Models\Supplier;
use App\Models\SupplierLocation;

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

    public function createSupplierLocation(
        Supplier $supplier,
        ?string $name = null,
        ?string $unique_identifier = null,
        ?string $description = null,
    ): SupplierLocation {
        $location = SupplierLocation::query()->create([
            'name' => $name ?? 'name'.rand(11, 999),
            'unique_identifier' => $unique_identifier ?? 'unique name'.rand(111, 999),
            'description' => $description ?? 'Test Description',
            'company_id' => $supplier->id,
        ]);

        return $location;
    }
}
