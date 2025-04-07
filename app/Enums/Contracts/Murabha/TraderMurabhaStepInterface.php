<?php

namespace App\Enums\Contracts\Murabha;

interface TraderMurabhaStepInterface
{
    public static function getSteps(?string $driver, ?string $version, $contract_type);
}
