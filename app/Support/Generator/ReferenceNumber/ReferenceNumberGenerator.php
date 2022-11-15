<?php

namespace App\Support\Generator\ReferenceNumber;

use App\Support\Generator\ReferenceNumber\Contracts\ReferenceNumberGeneratorInterFace;
use Illuminate\Support\Str;

class ReferenceNumberGenerator implements ReferenceNumberGeneratorInterFace
{
    /**
     * @return string
     */
    public function generate(): string
    {
        return Str::random(20);
    }
}
