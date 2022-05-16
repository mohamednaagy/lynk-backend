<?php

namespace Modules\TwoFactorAuth\Drivers;

use App\Models\User;
use App\Notifications\TwoFactorAuthCode;
use Modules\TwoFactorAuth\Contracts\TwoFactorAuthInterface;

class EmailTwoFactorAuthDriver implements TwoFactorAuthInterface
{

    /**
     * Execute the driver logic.
     *
     * @param
     * @return mixed
     */
    public function execute(User $user)
    {
        $user->notify(new TwoFactorAuthCode());
    }
}
