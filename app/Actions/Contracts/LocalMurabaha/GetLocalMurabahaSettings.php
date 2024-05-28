<?php

namespace App\Actions\Contracts\LocalMurabaha;

use App\Actions\Contracts\GetSettingsClassInstance;

interface GetLocalMurabahaSettings
{
    /**
     * UpdateLocalMurabahaTemplateAction constructor.
     *
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance);

    public function handle();
}