<?php

namespace App\Support\CompanySettings;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class Company
{
    private array $companyName;

    private string $companyCr;

    private string $vatId;

    private float $vat;

    private CompanyAddress $companyAddress;

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

    public function getCompanyNameToStore(): array
    {
        return $this->companyName;
    }

    public function getCompanyName(string $locale = null): string
    {
        if (is_null($locale)) {
            $locale = App::getLocale();
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

    public function getVatToStore(): float
    {
        return $this->vat / 100;
    }

    public function getVatToShow(): float
    {
        return $this->vat * 100;
    }

    public function getCompanyAddress(string $locale = null): CompanyAddress
    {
        return $this->companyAddress;
    }
}
