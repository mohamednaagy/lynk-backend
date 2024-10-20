<?php

namespace App\Observers;

use App\Actions\Contracts\Admins\AddAdminToAssignedList;
use App\Actions\Contracts\Admins\RemoveAdminFromAssignedList;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{

    public function __construct(
        protected AddAdminToAssignedList $addAdminToAssignedList,
        protected RemoveAdminFromAssignedList $removeAdminFromAssignedList
    ) {}

    /**
     * Handle the User "updated" event.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
        // Check if the "can_manage_orders" attribute was changed
        if ($user->wasChanged('can_manage_orders')) {
            $this->handleOrderAssignment($user);
        }
    }

    /**
     * Handle the addition or removal of the admin from the assigned list based on can_manage_orders value.
     *
     * @param User $user
     * @return void
     */
    protected function handleOrderAssignment(User $user): void
    {
        if ($user->can_manage_orders) {
            $this->addAdminToAssignedList->handle($user);
        } else {
            $this->removeAdminFromAssignedList->handle($user);
        }
    }
}
