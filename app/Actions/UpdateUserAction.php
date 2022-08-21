<?php

namespace App\Actions;

use App\Actions\Contracts\UpdateUser;
use App\Models\User;

class UpdateUserAction implements UpdateUser
{
    /**
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function handle(User $user, array $data): bool
    {
        $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code'])->formatE164();
        return $user->update($data);
    }
}
