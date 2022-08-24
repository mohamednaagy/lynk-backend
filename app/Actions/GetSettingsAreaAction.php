<?php

namespace App\Actions;

use App\Enums\Area;
use App\Actions\Contracts\GetSettingsArea;
use App\Actions\Contracts\SettingsInterface;

class GetSettingsAreaAction implements GetSettingsArea
{
    /**
     * @param string $key
     * @return SettingsInterface
     */
    public function handle(string $key): SettingsInterface
    {
        return match ($key) {
                'General' => app(GeneralSettingsAction::class),
                Area::SuperAdmin => app(SuperAdminSettingsAction::class),
                Area::Customer => app(CustomerSettingsAction::class),
            };
    }
}
