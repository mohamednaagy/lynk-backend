<?php

namespace App\Transformers;

use App\Support\InternationalMurabahaSettings\InternationalMurabaha;
use League\Fractal\TransformerAbstract;

class InternationalMurabahaSettingsTransformer extends TransformerAbstract
{
    /**
     * Transform the InternationalMurabaha settings into a JSON response format.
     */
    public function transform(InternationalMurabaha $murabaha): array
    {
        return [
            'bursam_default_preferred_commodity_type' => $murabaha->getBursamDefaultPreferredCommodityType(),
        ];
    }
}
