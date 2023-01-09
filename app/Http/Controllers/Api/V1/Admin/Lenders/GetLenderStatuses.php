<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;

class GetLenderStatuses extends Controller
{
    /**
     * @return array
     */
    public function __invoke(): array
    {
        return array_map(function ($value) {
            return [
                'value' => $value,
                'name' => CompanyStatus::getDescription($value),
            ];
        }, CompanyStatus::getValues());
    }
}
