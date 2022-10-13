<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface RegisterLender
{
    /**
     * @param  array  $data
     * @return User
     */
    public function handle(array $data): array;
}
