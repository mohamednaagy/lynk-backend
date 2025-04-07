<?php

namespace App\Actions\Contracts\Wakala;

use App\Actions\Contracts\GetSettingsClassInstance;

interface GetWakalaTemplate
{
    /**
     * UpdateWakalaTemplateAction constructor.
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance);

    public function handle(string $templateType): array;
}
