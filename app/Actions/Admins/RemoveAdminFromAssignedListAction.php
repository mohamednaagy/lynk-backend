<?php

namespace App\Actions\Admins;

use App\Actions\Contracts\Admins\RemoveAdminFromAssignedList;
use App\Models\User;
use App\Services\AdminOrderAssignmentService;

class RemoveAdminFromAssignedListAction implements RemoveAdminFromAssignedList
{

    public function __construct(protected AdminOrderAssignmentService $adminOrderAssignmentService) {}

    public function handle(User $user): User
    {
        $this->adminOrderAssignmentService->removeAdmin($user);
        return $user;
    }
}
