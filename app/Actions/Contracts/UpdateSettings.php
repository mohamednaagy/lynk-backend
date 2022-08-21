<?php

namespace App\Actions\Contracts;

interface UpdateSettings
{
    /**
     * @param array $data
     * @return void
     */
    public function handle(array $data): void;
}
