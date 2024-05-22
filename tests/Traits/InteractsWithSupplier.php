<?php

namespace Tests\Traits;

use App\Enums\CompanyType;
use App\Models\Supplier;

trait InteractsWithSupplier
{
    public function createSupplier(
        array $data = []
    ): Supplier {
        $data['type'] = CompanyType::Supplier;

        return Supplier::factory()->create($data);
    }
}
