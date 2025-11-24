<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\BuildPaginatedCompaniesQuery;
use App\Models\Lender;
use App\Support\QueryScoper\Scopes\Company\CompanyJoinedDateFromScope;
use App\Support\QueryScoper\Scopes\Company\CompanyJoinedDateToScope;
use App\Support\QueryScoper\Scopes\Company\CompanyMarketTypeScope;
use App\Support\QueryScoper\Scopes\Company\CompanyNameScope;
use App\Support\QueryScoper\Scopes\Company\CompanySearchScope;
use App\Support\QueryScoper\Scopes\Company\CompanyTradingModeScope;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCompaniesQueryAction implements BuildPaginatedCompaniesQuery
{
    public $type = null;

    public function handle(): Builder
    {
        return Lender::query()
            ->when($this->type, function ($query) {
                $query->type($this->type);
            })
            ->toScopes($this->scopes())
            ->withCount('orders');
    }

    private function scopes(): array
    {
        return [
            'search' => CompanySearchScope::class,
            'name' => CompanyNameScope::class,
            'from_date' => CompanyJoinedDateFromScope::class,
            'to_date' => CompanyJoinedDateToScope::class,
            'trading_mode' => CompanyTradingModeScope::class,
            'market_type' => CompanyMarketTypeScope::class,
        ];
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
