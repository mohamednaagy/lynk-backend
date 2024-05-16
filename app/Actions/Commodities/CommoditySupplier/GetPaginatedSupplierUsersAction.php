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
    protected ?Company $supplier = null;

    public function handle(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->supplier, function ($query) {
                return $query->where('company_id', $this->supplier->id)
                    ->withoutGlobalScope(TenantScope::class);
            })
            ->whereHas('roles', function ($query) {
                return $query->whereIn('name', [
                    Role::SupplierAdmin,
                ]);
            })
            ->with('permissions', 'roles')
            ->paginate();
    }

    public function setSupplier(Company $supplier)
    {
        $this->supplier = $supplier;

        return  $this;
    }
}
