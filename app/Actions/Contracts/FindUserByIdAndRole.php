<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface FindUserByIdAndRole
{
    public function handle(int $id, string $role): ?User;
}
