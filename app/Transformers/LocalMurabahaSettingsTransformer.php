<?php

namespace App\Transformers;

use App\Support\ProjectSettings\Project;
use League\Fractal\TransformerAbstract;
use Spatie\LaravelSettings\Settings;

class LocalMurabahaSettingsTransformer extends TransformerAbstract
{
    public function transform(Settings $settings): array
    {
        return [
            'default_trade_order_roatation_count' => $settings->default_trade_order_roatation_count,
        ];
    }
}
