<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\Traders\GetPaginatedTraderUsers;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Stancl\Tenancy\Database\TenantScope;

class GetPaginatedTraderUsersAction implements GetPaginatedTraderUsers
{
    protected Company $trader;

    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->trader, function ($query) {
                return $query->where('company_id', $this->trader->id)
                    ->withoutGlobalScope(TenantScope::class);
            })
            ->whereHas('roles', function ($query) {
                return $query->whereIn('name', [
                    Role::TraderAdmin,
                ]);
            })
            ->with('permissions', 'roles')
            ->paginate();
    }

    public function setTrader(Company $trader)
    {
        $this->trader = $trader;

        return  $this;
    }
}
