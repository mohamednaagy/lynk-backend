<?php

namespace App\Actions\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface GetPaginatedUsersByRole
{
    public function handle(string|array $role): Builder;

    public function setCanManageOrders(?bool $canManageOrders = null): self;

    public function setName(?string $name): self;

    public function setRole(?string $role): self;
}
