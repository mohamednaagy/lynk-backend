<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\Wakala\GetWakalaTemplate;
use App\Enums\Area;

class GetWakalaTemplateAction implements GetWakalaTemplate
{
    protected string $templateNameSuffix = '_wakala_template';

    /**
     * UpdateWakalaTemplateAction constructor.
     *
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     */
    public function __construct(
        protected GetSettingsClassInstance $getSettingsClassInstance
    ) {
    }

    public function handle(string $templateType): array
    {
        $wakalaTemplateName = $templateType.$this->templateNameSuffix;
        $settingInstance = $this->getSettingsClassInstance->handle(Area::SuperAdmin);

        return [
            'wakala_template' => $settingInstance->$wakalaTemplateName,
        ];
    }
}
