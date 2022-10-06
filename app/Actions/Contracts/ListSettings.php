<?php

namespace App\Actions\Contracts;

interface ListSettings
{
    /**
     * UpdateSettingsAction constructor.
     *
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance);

    /**
     * @return array
     */
    public function handle(): array;
}
