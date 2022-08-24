<?php

namespace App\Actions;

use App\Actions\Contracts\UpdateSettings;
use App\Actions\Contracts\GetSettingsArea;

class UpdateSettingsAction implements UpdateSettings
{
    /**
     * UpdateSettingsAction constructor.
     * @param GetSettingsArea $getSettingsArea
     */
    public function __construct(protected GetSettingsArea $getSettingsArea)
    {
    }

    /**
     * @param array $data
     * @return void
     */
    public function handle(array $data): void
    {
        $settingAction = $this->getSettingsArea->handle($data['area']);
        $settingAction->handle($data);
    }
}
