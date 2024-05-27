<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Enums\CompanyMarketType;
use App\Http\Controllers\Controller;

class GetLenderMarketTypes extends Controller
{
    public function __invoke(): array
    {
        return array_map(function ($value) {
            return [
                'value' => $value,
                'name' => CompanyMarketType::getDescription($value),
            ];
        }, CompanyMarketType::getValues());
    }
}
