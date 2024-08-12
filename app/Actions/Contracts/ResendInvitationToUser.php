<?php

namespace App\Actions\Contracts;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;

interface ResendInvitationToUser
{
    public function handle(Supplier|Company $company, User $user, string $redirect_url): void;
}
