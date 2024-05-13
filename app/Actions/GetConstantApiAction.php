<?php

namespace App\Actions;

use App\Actions\Contracts\GetConstantApi;
use App\Models\Currency;
use App\Models\Measurement;

class GetConstantApiAction implements GetConstantApi
{
    public function handle(array $data): array
    {
        $newData = [];
        if (isset($data['constants'])) {
            if (in_array('currencies', $data['constants'])) {
                $newData['currencies'] = Currency::get();
            }
            if (in_array('measurements', $data['constants'])) {
                $newData['measurements'] = Measurement::get();
            }
        }

        return $newData;

    }
}
