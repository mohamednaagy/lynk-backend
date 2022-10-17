<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\User;

interface RegisterLender
{
    /**
     * @param  array  $data
     * @return User
     */
    public function handle(array $data): User;
}
