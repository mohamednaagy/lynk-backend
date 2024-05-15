<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\GetPaginatedSupplierUsers;
use App\Enums\Role;
use App\Models\CommoditySupplier;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedSupplierUsersAction implements GetPaginatedSupplierUsers
{
    public function handle(CommoditySupplier $supplier): LengthAwarePaginator
    {
        return $supplier->users()
            ->whereHas('roles', function ($query) {
                return $query->whereIn('name', [
                    Role::SupplierAdmin,
                ]);
            })
            ->withCount('orders')
            ->with('permissions', 'roles')
            ->paginate();
    }
}
