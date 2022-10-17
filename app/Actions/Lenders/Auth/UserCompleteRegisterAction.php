<?php
namespace App\Actions\Lenders\Auth;

use App\Actions\Contracts\Lenders\Auth\UserCompleteRegister;
use App\Models\User;

class UserCompleteRegisterAction implements UserCompleteRegister
{
    public function handle(User $user, $data): User
    {
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->password = bcrypt($data['password']);
        $user->save();
        return $user;
    }
}
