<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\GetPaginatedSupplierUsers;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Grantify\Support\Areas\CommoditySupplier;

class GetPaginatedSupplierUsersAction implements GetPaginatedSupplierUsers
{
    public function handle(Company $company): LengthAwarePaginator
    {
        return $company->users()
            ->whereHas('roles', function ($query) {
                return $query->whereIn(
                    'name',
                    CommoditySupplier::$roles // TODO_LOCAL_MARKET replace role with area roles
                );
            })
            // TODO_LOCAL_MARKET we don't need this
            // ->withCount('orders')
            // ->with('permissions', 'roles')
            ->paginate();
    }
}
