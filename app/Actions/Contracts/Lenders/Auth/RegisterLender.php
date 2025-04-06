<?php

namespace App\Actions\Contracts\Lenders\Auth;

use App\Models\User;

interface RegisterLender
{
    public function handle(array $data): User;
}
