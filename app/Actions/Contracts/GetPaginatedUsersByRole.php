<?php

namespace App\Actions\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface GetPaginatedUsersByRole
{
    /**
     * @param  string  $role
     * @return Builder
     */
    public function handle(string|array $role): Builder;
}
