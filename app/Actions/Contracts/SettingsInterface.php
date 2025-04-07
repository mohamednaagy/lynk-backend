<?php

namespace App\Actions\Contracts;

interface SettingsInterface
{
    /**
     * UpdateSettingsAction constructor.
     */
    public function __construct(GetSettingsClassInstance $getSettingsClassInstance);

    public function handle(array $data): void;
}
