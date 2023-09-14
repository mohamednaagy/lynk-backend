<?php

namespace App\Enums\Contracts\Murabha;

interface TraderMurabhaStepInterface
{
    public static function getStepsOfVersion(string $driver = null, string $version = null);
}
