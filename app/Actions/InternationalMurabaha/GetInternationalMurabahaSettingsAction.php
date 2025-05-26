<?php

namespace App\Actions\InternationalMurabaha;

use App\Actions\Contracts\InternationalMurabaha\GetInternationalMurabahaSettings;
use App\Settings\Classes\InternationalMurabahaSetting;
use App\Support\InternationalMurabahaSettings\InternationalMurabaha;

class GetInternationalMurabahaSettingsAction implements GetInternationalMurabahaSettings
{
    /**
     * Get the InternationalMurabaha settings
     *
     * @return InternationalMurabaha The InternationalMurabaha settings
     */
    public function handle(): InternationalMurabaha
    {
        $murabahaSettings = app(InternationalMurabahaSetting::class);

        return InternationalMurabaha::fromArray($murabahaSettings->toArray());
    }
}
