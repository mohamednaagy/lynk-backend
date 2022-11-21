<?php

namespace App\Actions\Admins\Auth;

use App\Actions\Contracts\Admins\Auth\CompleteAdminRegistration;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class CompleteAdminRegistrationAction implements CompleteAdminRegistration
{
    public function __construct(protected UpdateUser $updateUser)
    {
    }

    public function handle(User $user, $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = Carbon::now()->toDateTimeString();

        $this->updateUser->handle($user, $data);

        return $user;
    }
}
