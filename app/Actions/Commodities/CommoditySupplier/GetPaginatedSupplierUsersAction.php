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

    public function handle(Company $supplier): LengthAwarePaginator
    {
        return User::query()
            ->where('company_id', $supplier->id)
            ->whereHas('roles', function ($query) {
                return $query->whereIn('name', [
                    Role::SupplierAdmin,
                    Role::SupplierApiAdmin,
                ]);
            })
            ->with('permissions', 'roles')
            ->paginate();
    }
}
