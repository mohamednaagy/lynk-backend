<?php

namespace App\Actions\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface GetPaginatedUsersByRole
{
    /**
     * @param  string|array $role
     * @return Builder
     */
    public function handle(string|array $role): Builder;

    public function setCanManageOrders(bool $canManageOrders = null): self;
}
