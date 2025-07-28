<?php

namespace App\Services\Tokens;

use App\Models\User;

interface TokenGeneratorInterface
{
    public function generateToken(User $user): TokenResult;
}
