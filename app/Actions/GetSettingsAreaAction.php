<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsArea;
use App\Actions\Contracts\SettingsInterface;
use App\Enums\Area;

class GetSettingsAreaAction implements GetSettingsArea
{
    public function handle(string $key): SettingsInterface
    {
        return match ($key) {
            'General' => app(GeneralSettingsAction::class),
            'LocalMurabaha' => app(LocalMurabahaSettingsAction::class),
            'InternationalMurabaha' => app(InternationalMurabahaSettingsAction::class),
            Area::SuperAdmin => app(SuperAdminSettingsAction::class),
            Area::Lender => app(LenderSettingsAction::class),
        };
    }
}
