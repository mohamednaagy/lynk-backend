<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\Wakala\UpdateWakalaTemplate;

class UpdateWakalaTemplateAction implements UpdateWakalaTemplate
{
    protected string $templateName = '_wakala_template';

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
        $wakalaTemplateName = $data['templateType'].$this->templateName;
        $settingInstance = $this->getSettingsClassInstance->handle($data['area']);

        $settingInstance->$wakalaTemplateName = $data['wakala_template'];

        $settingInstance->save();
    }
}
