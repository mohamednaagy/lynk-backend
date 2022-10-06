<?php

namespace App\Actions\Contracts;

interface GetSettingsArea
{
    /**
     * @param  string  $key
     * @return SettingsInterface
     */
    public function handle(string $key): SettingsInterface;
}
