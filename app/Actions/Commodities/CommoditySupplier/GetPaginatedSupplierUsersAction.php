<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\GetPaginatedSupplierUsers;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Stancl\Tenancy\Database\TenantScope;

class GetPaginatedSupplierUsersAction implements GetPaginatedSupplierUsers
{
    protected ?Company $company = null;

    public function handle(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->company, function ($query) {
                return $query->where('company_id', $this->company->id)
                    ->withoutGlobalScope(TenantScope::class);
            })
            ->whereHas('roles', function ($query) {
                return $query->whereIn('name', [
                    Role::Admin,
                    Role::SupplierAdmin,
                    Role::SupplierApiAdmin,
                ]);
            })
            ->with('permissions', 'roles')
            ->paginate();
    }

    public function setSupplier(Company $company)
    {
        $this->company = $company;

        return $this;
    }
}
