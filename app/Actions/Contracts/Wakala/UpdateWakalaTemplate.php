<?php

namespace App\Actions\Contracts\Wakala;

use App\Actions\Contracts\GetSettingsClassInstance;

interface UpdateWakalaTemplate
{
    /**
     * UpdateWakalaTemplateAction constructor.
     *
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance);

    public function handle(array $data): void;
}
