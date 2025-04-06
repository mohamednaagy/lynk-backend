<?php

namespace App\Actions\Contracts;

interface ListSettings
{
    /**
     * UpdateSettingsAction constructor.
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance);

    public function handle(): array;
}
