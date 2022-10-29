<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsArea;
use App\Actions\Contracts\SettingsInterface;
use App\Enums\Area;

class GetSettingsAreaAction implements GetSettingsArea
{
    /**
     * @param  string  $key
     * @return SettingsInterface
     */
    public function handle(string $key): SettingsInterface
    {
        return match ($key) {
            'General' => app(GeneralSettingsAction::class),
            Area::SuperAdmin => app(SuperAdminSettingsAction::class),
            Area::Customer => app(CustomerSettingsAction::class),
            Area::Lender => app(LenderSettingsAction::class),
        };
    }
}
