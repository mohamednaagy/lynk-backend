<?php

namespace App\Enums\Contracts\Murabha;

interface MurabhaStepsInterface
{
    public static function getStepsOfVersion(?string $version = null);
}
