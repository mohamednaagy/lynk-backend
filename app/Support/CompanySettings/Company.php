<?php

namespace App\Support\CompanySettings;

use Illuminate\Support\Arr;

class Company
{
    private array $companyName;

    private string $companyCr;

    private string $vatId;

    private float $vat;

    private CompanyAddress $companyAddress;

    /**
     * @param  array  $companyName
     * @param  string  $companyCr
     * @param  string  $vatId
     * @param  float  $vat
     * @param  CompanyAddress  $companyAddress
     */
    public function __construct(
        array $companyName,
        string $companyCr,
        string $vatId,
        float $vat,
        CompanyAddress $companyAddress
    ) {
        $this->companyName = $companyName;
        $this->companyCr = $companyCr;
        $this->vatId = $vatId;
        $this->vat = $vat;
        $this->companyAddress = $companyAddress;
    }

    /**
     * @param  array  $data
     * @return Company
     */
    public static function fromArray(array $data): Company
    {
        return new static(
            $data['company_name'],
            $data['company_cr'],
            $data['vat_id'],
            $data['vat'],
            CompanyAddress::fromArray($data['address_line_one'], $data['address_line_two']),
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
    public function getVat(): float
    {
        return $this->vat / 100;
    }

    /**
     * @return float
     */
    public function getVatPercentage(): float
    {
        return $this->vat * 100;
    }

    /**
     * @param  string|null  $locale
     * @return CompanyAddress
     */
    public function getCompanyAddress(string $locale = null): CompanyAddress
    {
        return $this->companyAddress;
    }
}
