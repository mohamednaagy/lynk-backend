<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\Wakala\UpdateWakalaTemplate;
use App\Enums\Area;
use Mews\Purifier\Facades\Purifier;

class UpdateWakalaTemplateAction implements UpdateWakalaTemplate
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

    public function handle(array $data): void
    {
        $wakalaTemplateName = $data['template_type'].$this->templateNameSuffix;
        $settingInstance = $this->getSettingsClassInstance->handle(Area::SuperAdmin);

        $settingInstance->$wakalaTemplateName = Purifier::clean($data['wakala_template']);

        $settingInstance->save();
    }
}
