<?php

namespace App\Actions\Contracts\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface BuildUserNotificationsQuery
{
    public function handle(): Builder;

    public function setUser(User $user): self;

    public function setUnreadOnly(bool $unreadOnly): self;
}
