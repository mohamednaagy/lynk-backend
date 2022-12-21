<?php

namespace App\Actions\Admins\Auth;

use App\Actions\Contracts\Admins\Auth\CompleteAdminRegistration;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;

class CompleteAdminRegistrationAction implements CompleteAdminRegistration
{
    public function __construct(protected UpdateUser $updateUser)
    {
    }

    public function handle(User $user, $data): User
    {
        $data['email_verified_at'] = now();

        $this->updateUser->handle($user, $data);

        return $user;
    }
}
