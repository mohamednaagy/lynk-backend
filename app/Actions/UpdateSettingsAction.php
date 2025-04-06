<?php

namespace App\Actions;

use App\Actions\Contracts\GetSettingsArea;
use App\Actions\Contracts\UpdateSettings;

class UpdateSettingsAction implements UpdateSettings
{
    /**
     * UpdateSettingsAction constructor.
     */
    public function __construct(
        protected GetSettingsArea $getSettingsArea
    ) {}

    public function handle(array $data): void
    {
        $settingAction = $this->getSettingsArea->handle($data['area']);
        $settingAction->handle($data);
    }
}
