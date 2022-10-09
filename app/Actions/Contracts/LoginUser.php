<?php

namespace App\Actions\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

interface LoginUser
{
    public function handle(User $user, string $source, Request|null $request): array;
}
