<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\BuildPaginatedCompaniesQuery;
use App\Models\Company;
use App\Support\QueryScoper\Scopes\Company\CompanySearchScope;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCompaniesQueryAction implements BuildPaginatedCompaniesQuery
{
    public $type = null;

    public function handle(): Builder
    {
        return Company::query()
            ->when($this->type, function ($query) {
                $query->type($this->type);
            })
            ->toScopes($this->scopes())
            ->withCount('orders');
    }

    private function scopes(): array
    {
        return [
            'search' => new CompanySearchScope(),
        ];
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
