<?php

namespace Modules\TwoFactorAuth\Contracts;

use App\Models\User;

interface TwoFactorAuthInterface
{
    /**
     * Execute the driver logic.
     *
     * @param
     * @return mixed
     */
    public function execute(User $user);

}
