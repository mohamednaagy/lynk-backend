<?php

namespace App\Actions;

use App\Actions\Contracts\GetConstantApi;
use App\Enums\NotificationChannel;
use App\Models\Currency;
use App\Models\Measurement;
use Psl\Collection\Set;

class GetConstantApiAction implements GetConstantApi
{
    public function handle(array $data): array
    {
        $constants = [];
        if (isset($data['constants'])) {
            $set = new Set($data['constants']);
            if ($set->contains('currencies')) {
                $constants['currencies'] = Currency::get();
            }

            if ($set->contains('measurements')) {
                $constants['measurements'] = Measurement::get();
            }

            if ($set->contains('channels')) {
                $constants['channels'] = NotificationChannel::cases();
            }
        }

        return $constants;
    }
}
