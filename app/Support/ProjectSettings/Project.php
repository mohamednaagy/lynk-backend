<?php

namespace App\Support\ProjectSettings;

use Illuminate\Support\Arr;

class Project
{
    private array $companyName;

    private string $companyCr;

    private string $vatId;

    private float $vat;

    private ProjectAddress $projectAddress;

    /**
     * @param  array  $companyName
     * @param  string  $companyCr
     * @param  string  $vatId
     * @param  float  $vat
     * @param  ProjectAddress  $projectAddress
     */
    public function __construct(
        array $companyName,
        string $companyCr,
        string $vatId,
        float $vat,
        ProjectAddress $projectAddress
    ) {
        $this->companyName = $companyName;
        $this->companyCr = $companyCr;
        $this->vatId = $vatId;
        $this->vat = $vat;
        $this->projectAddress = $projectAddress;
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
            $data['vat'],
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
     * @return ProjectAddress
     */
    public function getCompanyAddress(string $locale = null): ProjectAddress
    {
        return $this->projectAddress;
    }
}
