<?php

namespace App\Actions\Contracts;

interface SettingsInterface
{
    /**
     * UpdateSettingsAction constructor.
     * @param GetSettingsClassInstance $getSettingsClassInstance
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance);

    /**
     * @param array $data
     * @return void
     */
    public function handle(array $data): void;
}
