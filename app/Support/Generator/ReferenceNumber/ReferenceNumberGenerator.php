<?php

namespace App\Support\Generator\ReferenceNumber;

use App\Support\Generator\ReferenceNumber\Contracts\ReferenceNumberGeneratorInterface;
use Illuminate\Support\Str;

class ReferenceNumberGenerator implements ReferenceNumberGeneratorInterface
{
    public function generate(): string
    {
        return Str::random(20);
    }
}
