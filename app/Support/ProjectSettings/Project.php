<?php

namespace App\Support\ProjectSettings;

use Illuminate\Support\Arr;

class Project
{
    /**
     * @param  array  $companyName
     * @param  string  $companyCr
     * @param  string  $vatId
     * @param  float  $vatRate
     * @param  ProjectAddress  $projectAddress
     */
    public function __construct(
        private array $companyName,
        private string $companyCr,
        private string $vatId,
        private float $vatRate,
        private ProjectAddress $projectAddress
    ) {
    }

    /**
     * @param  array  $data
     * @return Project
     */
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

    /**
     * @param  string|null  $locale
     * @return array|string
     */
    public function getCompanyName(string $locale = null): array|string
    {
        if (is_null($locale)) {
            return $this->companyName;
        }

        return Arr::get($this->companyName, $locale);
    }

    /**
     * @return string
     */
    public function getCompanyCr(): string
    {
        return $this->companyCr;
    }

    /**
     * @return string
     */
    public function getVatId(): string
    {
        return $this->vatId;
    }

    /**
     * @return float
     */
    public function getVatRate(): float
    {
        return $this->vatRate;
    }

    /**
     * @return float
     */
    public function getVatRateInPercentage(): float
    {
        return $this->vatRate * 100;
    }

    /**
     * @return ProjectAddress
     */
    public function getCompanyAddress(): ProjectAddress
    {
        return $this->projectAddress;
    }
}
