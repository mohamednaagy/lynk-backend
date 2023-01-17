<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;

class GetCompanyStatuses extends Controller
{
    public function __invoke()
    {
        return array_map(function ($value) {
            return [
                'value' => $value,
                'name' => CompanyStatus::getDescription($value),
            ];
        }, CompanyStatus::getValues());
    }
}
