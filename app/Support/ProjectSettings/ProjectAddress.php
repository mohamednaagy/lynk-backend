<?php

namespace App\Support\ProjectSettings;

use Illuminate\Support\Arr;

class ProjectAddress
{
    public function __construct(
        private array $addressLineOne,
        private array $addressLineTwo,
    ) {}

    public static function fromArray(array $addressLineOneData, array $addressLineTwoData): ProjectAddress
    {
        return new static(
            $addressLineOneData,
            $addressLineTwoData,
        );
    }

    public function getAddressLineOne(?string $locale = null): array|string
    {
        if (is_null($locale)) {
            return $this->addressLineOne;
        }

        return Arr::get($this->addressLineOne, $locale);
    }

    public function getAddressLineTwo(?string $locale = null): array|string
    {
        if (is_null($locale)) {
            return $this->addressLineTwo;
        }

        return Arr::get($this->addressLineTwo, $locale);
    }
}
