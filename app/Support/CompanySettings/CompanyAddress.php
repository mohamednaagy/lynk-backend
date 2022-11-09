<?php

namespace App\Support\CompanySettings;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class CompanyAddress
{
    private array $addressLineOne;

    private array $addressLineTwo;

    public function __construct(
        array $addressLineOne,
        array $addressLineTwo,
    ) {
        $this->addressLineOne = $addressLineOne;
        $this->addressLineTwo = $addressLineTwo;
    }

    public static function fromArray(array $addressLineOneData, array $addressLineTwoData): CompanyAddress
    {
        return new static(
            $addressLineOneData,
            $addressLineTwoData,
        );
    }

    public function getAddressLineOneToStore(): array
    {
        return $this->addressLineOne;
    }

    public function getAddressLineOne(string $locale = null): string
    {
        if (is_null($locale)) {
            $locale = App::getLocale();
        }

        return Arr::get($this->addressLineOne, $locale);
    }

    public function getAddressLineTwoToStore(): array
    {
        return $this->addressLineTwo;
    }

    public function getAddressLineTwo(string $locale = null): string
    {
        if (is_null($locale)) {
            $locale = App::getLocale();
        }

        return Arr::get($this->addressLineTwo, $locale);
    }
}
