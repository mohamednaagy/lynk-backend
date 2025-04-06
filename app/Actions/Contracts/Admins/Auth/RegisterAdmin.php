<?php

namespace App\Actions\Contracts\Admins\Auth;

use App\Models\User;

interface RegisterAdmin
{
    public function handle(array $data): User;
}
