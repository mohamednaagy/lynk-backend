<?php

namespace App\Settings\Casters;

use App\Support\ProjectSettings\Project;
use App\Support\ProjectSettings\ProjectAddress;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class ProjectCaster implements SettingsCast
{
    /**
     * @param $payload
     * @return Project
     */
    public function get($payload): Project
    {
        return new Project(
            $payload['company_name'],
            $payload['company_cr'],
            $payload['vat_id'],
            $payload['vat_rate'],
            ProjectAddress::fromArray($payload['address_line_one'], $payload['address_line_two']),
        );
    }

    /**
     * @param $payload
     * @return array
     */
    public function set($payload): array
    {
        return [
            'company_name' => [
                'en' => $payload->getCompanyName('en'),
                'ar' => $payload->getCompanyName('ar'),
            ],
            'company_cr' => $payload->getCompanyCr(),
            'vat_id' => $payload->getVatId(),
            'vat_rate' => $payload->getVatRate(),
            'address_line_one' => [
                'en' => $payload->getCompanyAddress()->getAddressLineOne('en'),
                'ar' => $payload->getCompanyAddress()->getAddressLineOne('ar'),
            ],
            'address_line_two' => [
                'en' => $payload->getCompanyAddress()->getAddressLineTwo('en'),
                'ar' => $payload->getCompanyAddress()->getAddressLineTwo('ar'),
            ],
        ];
    }
}
