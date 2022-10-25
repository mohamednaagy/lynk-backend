<?php

namespace App\Actions;

use App\Actions\Contracts\VerifyEmail;
use App\Models\User;

class VerifyEmailAction implements VerifyEmail
{
    public function handle(User $user)
    {
        $user->email_verified_at = now();
        $user->save();
    }
}
