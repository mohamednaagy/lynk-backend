<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrder;
use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\TenantScope;

class GetOrderAction implements GetOrder
{
    protected ?Company $company = null;

    public function handle(int $order): Model|Collection|Builder|array|null
    {
        return $this->baseQuery()->toScopes($this->scopes())->findOrFail($order);
    }

    private function scopes(): array
    {
        return [];
    }

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    protected function baseQuery()
    {
        return FinancingOrder::when(
            filled($this->company) && $this->company->type->is(CompanyType::Trader),
            function ($query) {
                $query->withoutGlobalScope(TenantScope::class)
                    ->withWhereHas('traderOrders', function ($query) {
                        $query->withWhereHas('traderOrders', function ($query) {
                            $query->with('traderHistories');
                        })->where('provider', $this->company->driver);
                    })->where('company_id', $this->company->id);
            }
        );
    }
}
