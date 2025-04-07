<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface CreateUser
{
    public function handle(array $data): User;
}
