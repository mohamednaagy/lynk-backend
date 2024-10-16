<?php

namespace App\Actions\Admins;

use App\Actions\Contracts\Admins\RemoveAdminFromAssignedList;
use App\Models\User;
use App\Services\AssignOrdersToAdminService;

class RemoveAdminFromAssignedListAction implements RemoveAdminFromAssignedList
{
    private $assignOrdersToAdminService;

    public function __construct(AssignOrdersToAdminService $assignOrdersToAdminService)
    {
        $this->assignOrdersToAdminService = $assignOrdersToAdminService;
    }

    public function handle(User $user): User
    {
        $this->assignOrdersToAdminService->removeAdmin($user->id);
        return $user;
    }
}
