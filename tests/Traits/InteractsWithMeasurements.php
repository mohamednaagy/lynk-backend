<?php

namespace Tests\Traits;

use App\Models\Measurement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

trait InteractsWithMeasurements
{
    public function getMeasurements($number_of_objects = 5, $is_paginate = false): LengthAwarePaginator|Collection
    {
        for ($i = 0; $i < $number_of_objects; $i++) {
            $this->createMeasurement();
        }

        $measurements = Measurement::query();
        if ($is_paginate) {
            return $measurements->paginate();
        }

        return $measurements->get();

    }

    public function createMeasurement(?string $name = 'Tons', ?string $symbol = 't'): Model|Builder
    {

        return Measurement::query()->firstOrCreate([
            'name' => $name,
            'symbol' => $symbol,
        ]);
    }
}
