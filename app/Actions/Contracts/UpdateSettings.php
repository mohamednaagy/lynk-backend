<?php

namespace App\Actions\Contracts;

interface UpdateSettings
{
    /**
     * UpdateSettingsAction constructor.
     *
     * @param  GetSettingsArea  $getSettingsArea
     */
    public function __construct(GetSettingsArea $getSettingsArea);

    /**
     * @param  array  $data
     * @return void
     */
    public function handle(array $data): void;
}
