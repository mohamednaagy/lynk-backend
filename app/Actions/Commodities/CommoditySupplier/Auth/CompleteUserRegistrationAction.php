<?php

namespace App\Actions\Commodities\CommoditySupplier\Auth;

use App\Actions\Contracts\Commodities\CommoditySupplier\Auth\CompleteUserRegistration;
use App\Models\User;
use DragonCode\Support\Facades\Helpers\Arr;

class CompleteUserRegistrationAction implements CompleteUserRegistration
{
    public function handle(User $user, $data): User
    {
        $user->update(
            Arr::only(
                $data,
                [
                    'first_name',
                    'last_name',
                    'password',
                ]
            )
        );
       

        $user->markEmailAsVerified();

        return $user;
    }
}
