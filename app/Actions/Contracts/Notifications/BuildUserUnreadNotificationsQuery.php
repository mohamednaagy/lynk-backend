<?php

namespace App\Actions\Contracts\Notifications;

use App\Models\User;

interface BuildUserUnreadNotificationsQuery
{
    public function handle(): int;

    public function setUser(User $user): self;
}
