<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface VerifyEmail
{
    public function handle(User $user);
}
