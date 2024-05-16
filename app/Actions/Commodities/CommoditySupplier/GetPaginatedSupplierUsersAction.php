<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\GetPaginatedSupplierUsers;
use App\Enums\Role;
use App\Models\CommoditySupplier;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Stancl\Tenancy\Database\TenantScope;

class GetPaginatedSupplierUsersAction implements GetPaginatedSupplierUsers
{
    protected ?CommoditySupplier $supplier = null;

    public function handle(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->supplier, function ($query) {
                return $query->where('company_id', $this->supplier->company_id)
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

    public function setSupplier(CommoditySupplier $supplier)
    {
        $this->supplier = $supplier;

        return $this;
    }
}
