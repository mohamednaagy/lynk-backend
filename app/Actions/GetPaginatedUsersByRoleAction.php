<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GetPaginatedUsersByRoleAction implements GetPaginatedUsersByRole
{
    protected ?bool $canManageOrders = null;

    protected ?string $name = null;

    protected ?string $role = null;

    public function handle(string|array $role): Builder
    {
        return User::role($role)
            ->with('roles', 'permissions')
            ->when($this->canManageOrders, function ($builder) {
                return $builder->where('can_manage_orders', $this->canManageOrders);
            })->when($this->name, function (Builder $builder) {
                return $this->applyNameFilter($builder);
            })->when($this->role, function (Builder $builder) {
                return $this->applyRoleFilter($builder);
            });
    }

    public function setCanManageOrders(?bool $canManageOrders = null): self
    {
        $this->canManageOrders = $canManageOrders;

        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function applyNameFilter(Builder $builder): Builder
    {
        return $builder->where(function ($query) {
            $query->where('first_name', 'like', "%{$this->name}%")
                ->orWhere('last_name', 'like', "%{$this->name}%")
                ->orWhere(fn (Builder $q) => $q->whereFullNameLike($this->name));
        });
    }

    public function applyRoleFilter(Builder $builder): Builder
    {
        return $builder->whereHas('roles', function (Builder $query) {
            $query->where('name', $this->role);
        });
    }
}
