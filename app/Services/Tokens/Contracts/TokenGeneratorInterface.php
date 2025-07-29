<?php

namespace App\Services\Tokens\Contracts;

use App\Models\User;
use App\Services\Tokens\TokenResult;

interface TokenGeneratorInterface
{
    /**
     * Generate a basic (signed) JWT token for the given user.
     */
    public function generateToken(User $user): TokenResult;

    /**
     * Generate a token with a specific mode (sign, encrypt, sign_encrypt).
     */
    public function generateTokenWithMode(User $user, string $mode): TokenResult;
}
