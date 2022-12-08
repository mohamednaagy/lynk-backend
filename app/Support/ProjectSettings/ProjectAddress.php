<?php

namespace App\Support\ProjectSettings;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class ProjectAddress
{
    /**
     * @param  array  $addressLineOne
     * @param  array  $addressLineTwo
     */
    public function __construct(
        private array $addressLineOne,
        private array $addressLineTwo,
    ) {
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
            return Arr::get($this->addressLineOne, Config::get('app.locale', 'en'));
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
            return Arr::get($this->addressLineTwo, Config::get('app.locale', 'en'));
        }

        return Arr::get($this->addressLineTwo, $locale);
    }
}
