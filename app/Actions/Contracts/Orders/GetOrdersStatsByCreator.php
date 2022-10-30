<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Company;
use App\Models\User;

interface GetOrdersStatsByCreator
{
    public function handle(Company $company, User $user);
}
