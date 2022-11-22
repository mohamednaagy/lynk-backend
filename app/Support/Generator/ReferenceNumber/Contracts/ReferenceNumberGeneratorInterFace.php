<?php

namespace App\Support\Generator\ReferenceNumber\Contracts;

interface ReferenceNumberGeneratorInterface
{
    public function generate(): string;
}
