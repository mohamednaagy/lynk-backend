<?php

namespace App\Actions\Admins;

use App\Actions\Contracts\Admins\AddAdminToAssignedList;
use App\Models\User;
use App\Services\AdminOrderAssignmentService;

class AddAdminToAssignedListAction implements AddAdminToAssignedList
{
    private $adminOrderAssignmentService;

    public function __construct(AdminOrderAssignmentService $adminOrderAssignmentService)
    {
        $this->adminOrderAssignmentService = $adminOrderAssignmentService;
    }

    public function handle(User $user): User
    {
        $this->adminOrderAssignmentService->addAdmin($user);
        return $user;
    }
}
