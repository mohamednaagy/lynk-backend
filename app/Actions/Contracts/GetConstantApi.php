<?php

namespace App\Actions\Contracts;

interface GetConstantApi
{
    public function handle(array $data): array;
}
