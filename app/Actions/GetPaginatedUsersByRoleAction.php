<?php

namespace App\Actions;

use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GetPaginatedUsersByRoleAction implements GetPaginatedUsersByRole
{
    protected ?bool $canManageOrders = null;
    /**
     * @param  string|array  $role
     * @return Builder
     */
    public function handle(string|array $role): Builder
    {
        return User::role($role)
            ->with('roles', 'permissions')
            ->when($this->canManageOrders, function($builder) {
                return $builder->where('can_manage_orders', $this->canManageOrders);
            });
    }

    public function setCanManageOrders(bool $canManageOrders = null): self
    {
        $this->canManageOrders = $canManageOrders;
        return $this;
    }
}
