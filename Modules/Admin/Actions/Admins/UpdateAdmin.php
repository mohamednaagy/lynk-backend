<?php

namespace Modules\Admin\Actions\Admins;

use App\Models\User;

class UpdateAdmin
{
    /**
     * Create new user.
     * @param array $data
     * @param User $user
     * @return void
     */
    public function handle(array $data, User $user): void
    {
        $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code'])->formatE164();
        $user->update($data);

        if (!empty($data['role']))
            \Grantify::syncRoleToModel($user, $data['role']);

        if (!empty($data['permissions']))
            \Grantify::syncPermissionToModel($user, $data['permissions']);
    }
}
