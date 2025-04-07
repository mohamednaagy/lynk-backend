<?php

namespace App\Actions\Contracts\LocalMurabaha;

use App\Actions\Contracts\GetSettingsClassInstance;

interface UpdateLocalMurabahaSettings
{
    /**
     * UpdateWakalaTemplateAction constructor.
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance);

    public function handle(array $data): void;
}
