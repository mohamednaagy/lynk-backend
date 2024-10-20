<?php

namespace App\Actions\Admins;

use App\Actions\Contracts\Admins\AddAdminToAssignedList;
use App\Models\User;
use App\Services\AdminOrderAssignmentService;

class AddAdminToAssignedListAction implements AddAdminToAssignedList
{

    public function __construct(protected AdminOrderAssignmentService $adminOrderAssignmentService)
    {
    }

    public function handle(User $user): User
    {
        $this->adminOrderAssignmentService->addAdmin($user);
        return $user;
    }
}
