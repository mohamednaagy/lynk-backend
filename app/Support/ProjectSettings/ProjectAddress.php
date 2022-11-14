<?php

namespace App\Support\ProjectSettings;

use Illuminate\Support\Arr;

class ProjectAddress
{
    private array $addressLineOne;

    private array $addressLineTwo;

    /**
     * @param  array  $addressLineOne
     * @param  array  $addressLineTwo
     */
    public function __construct(
        array $addressLineOne,
        array $addressLineTwo,
    ) {
        $this->addressLineOne = $addressLineOne;
        $this->addressLineTwo = $addressLineTwo;
    }

    /**
     * @param  array  $addressLineOneData
     * @param  array  $addressLineTwoData
     * @return ProjectAddress
     */
    public static function fromArray(array $addressLineOneData, array $addressLineTwoData): ProjectAddress
    {
        return new static(
            $addressLineOneData,
            $addressLineTwoData,
        );
    }

    /**
     * @param  string|null  $locale
     * @return array|string
     */
    public function getAddressLineOne(string $locale = null): array|string
    {
        if (is_null($locale)) {
            return $this->addressLineOne;
        }

        return Arr::get($this->addressLineOne, $locale);
    }

    /**
     * @param  string|null  $locale
     * @return array|string
     */
    public function getAddressLineTwo(string $locale = null): array|string
    {
        if (is_null($locale)) {
            return $this->addressLineTwo;
        }

        return Arr::get($this->addressLineTwo, $locale);
    }
}
