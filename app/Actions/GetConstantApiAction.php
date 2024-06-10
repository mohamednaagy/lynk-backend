<?php

namespace App\Actions;

use App\Actions\Contracts\GetConstantApi;
use App\Models\Currency;
use App\Models\Measurement;

class GetConstantApiAction implements GetConstantApi
{
    public function handle(array $data): array
    {
        $constants = [];
        if (isset($data['constants'])) {
            if (in_array('currencies', $data['constants'])) {
                $constants['currencies'] = Currency::get();
            }
            if (in_array('measurements', $data['constants'])) {
                $constants['measurements'] = Measurement::get();
            }
        }

        return $constants;

    }
}
