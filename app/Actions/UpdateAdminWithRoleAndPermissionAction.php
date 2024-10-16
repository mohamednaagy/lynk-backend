<?php

namespace App\Actions;

use App\Actions\Contracts\Admins\AddAdminToAssignedList;
use App\Actions\Contracts\Admins\RemoveAdminFromAssignedList;
use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UpdateAdminWithRoleAndPermissionAction implements UpdateAdminWithRoleAndPermission
{
    /**
     * @param  SyncRoleToUser  $syncRoleToUser
     * @param  SyncPermissionToUser  $syncPermissionToUser
     */
    public function __construct(
        protected UpdateUser $updateUser,
        protected SyncRoleToUser $syncRoleToUser,
        protected SyncPermissionToUser $syncPermissionToUser,
        protected AddAdminToAssignedList $addAdminToAssignedList,
        protected RemoveAdminFromAssignedList $removeAdminFromAssignedList
    ) {
    }

    /**
     * Create new user.
     *
     * @param  array  $data
     * @param  User  $user
     * @return void
     */
    public function handle(array $data, User $user): void
    {
        // update user
        $this->updateUser->handle($user, $data);

        //can assign order to admin
        if ($data['can_assign_order'] == true) {
            // Add admin to the assigned list
            $this->addAdminToAssignedList->handle($user);
            Log::info("Added admin to assigned list: {$user->id}");
        } else {
            // Remove admin from the assigned list
            $this->removeAdminFromAssignedList->handle($user);
            Log::info("Removed admin from assigned list: {$user->id}");
        }

        // sync role
        if (! empty($data['role'])) {
            $this->syncRoleToUser->handle($user, $data['role']);
        }

        // sync permission
        if (! empty($data['permissions'])) {
            $this->syncPermissionToUser->handle($user, $data['permissions']);
        }
    }
}
