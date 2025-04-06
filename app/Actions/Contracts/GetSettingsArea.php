<?php

namespace App\Actions\Contracts;

interface GetSettingsArea
{
    public function handle(string $key): SettingsInterface;
}
