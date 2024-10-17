<?php

namespace App\Actions\Admins;

use App\Actions\Contracts\Admins\RemoveAdminFromAssignedList;
use App\Models\User;
use App\Services\AdminOrderAssignmentService;

class RemoveAdminFromAssignedListAction implements RemoveAdminFromAssignedList
{
    private $adminOrderAssignmentService;

    public function __construct(AdminOrderAssignmentService $adminOrderAssignmentService)
    {
        $this->adminOrderAssignmentService = $adminOrderAssignmentService;
    }

    public function handle(User $user): User
    {
        $this->adminOrderAssignmentService->removeAdmin($user);
        return $user;
    }
}
