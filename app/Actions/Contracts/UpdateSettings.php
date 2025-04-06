<?php

namespace App\Actions\Contracts;

interface UpdateSettings
{
    /**
     * UpdateSettingsAction constructor.
     */
    public function __construct(GetSettingsArea $getSettingsArea);

    public function handle(array $data): void;
}
