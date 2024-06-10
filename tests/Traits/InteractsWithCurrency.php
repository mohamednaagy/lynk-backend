<?php

namespace Tests\Traits;

use App\Models\Currency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

trait InteractsWithCurrency
{
    public function getCurrencies($number_of_objects = 5, $is_paginate = false): LengthAwarePaginator|Collection
    {
        for ($i = 0; $i < $number_of_objects; $i++) {
            $this->createCurrency();
        }

        $currencies = Currency::query();
        if ($is_paginate) {
            return $currencies->paginate();
        }

        return $currencies->get();

    }

    public function createCurrency(?string $name = 'SAR'): Model|Builder
    {

        return Currency::query()->firstOrCreate([
            'name' => $name,
        ]);
    }
}
