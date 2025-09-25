<?php

namespace App\Actions\Companies;

use App\Enums\CompanyStatus;
use App\Models\Lender;
use Arr;
class SpecialPurposeVehicleCompanyCreationStrategy implements CompanyCreationStrategy
{
    public function create(array $data): Lender
    {
        $data['status'] = CompanyStatus::Approved();
        return Lender::create(
            Arr::only(
                $data,
                [
                    'name',
                    'status',
                    'type',
                ]
            )
        );
    }
}