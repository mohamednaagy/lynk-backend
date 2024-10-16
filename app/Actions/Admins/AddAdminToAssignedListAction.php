<?php

namespace App\Actions\Admins;

use App\Actions\Contracts\Admins\AddAdminToAssignedList;
use App\Models\User;
use App\Services\AssignOrdersToAdminService;

class AddAdminToAssignedListAction implements AddAdminToAssignedList
{
    private $assignOrdersToAdminService;

    public function __construct(AssignOrdersToAdminService $assignOrdersToAdminService)
    {
        $this->assignOrdersToAdminService = $assignOrdersToAdminService;
    }

    public function handle(User $user): User
    {
        $this->assignOrdersToAdminService->addAdmin($user->id);
        return $user;
    }
}
