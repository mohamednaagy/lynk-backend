<?php

namespace App\Support\ProjectSettings;

use Illuminate\Support\Arr;

class Project
{
    public function __construct(
        private array $companyName,
        private string $companyCr,
        private string $vatId,
        private float $vatRate,
        private ProjectAddress $projectAddress
    ) {}

    public static function fromArray(array $data): Project
    {
        return new static(
            $data['company_name'],
            $data['company_cr'],
            $data['vat_id'],
            $data['vat_rate'],
            ProjectAddress::fromArray($data['address_line_one'], $data['address_line_two']),
        );
    }

    public function getCompanyName(?string $locale = null): array|string
    {
        if (is_null($locale)) {
            return $this->companyName;
        }

        return Arr::get($this->companyName, $locale);
    }

    public function getCompanyCr(): string
    {
        return $this->companyCr;
    }

    public function getVatId(): string
    {
        return $this->vatId;
    }

    public function getVatRate(): float
    {
        return $this->vatRate ?? 0.0;
    }

    public function getVatRateInPercentage(): float
    {
        return $this->vatRate * 100;
    }

    public function getCompanyAddress(): ProjectAddress
    {
        return $this->projectAddress;
    }
}
