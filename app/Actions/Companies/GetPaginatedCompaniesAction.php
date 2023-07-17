<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetPaginatedCompanies;
use App\Models\Company;
use App\Support\QueryScoper\Scopes\Company\CompanySearchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedCompaniesAction implements GetPaginatedCompanies
{
    public $type = null;

    public function handle(): LengthAwarePaginator
    {
        return Company::query()
            ->when($this->type, function ($query) {
                $query->type($this->type);
            })
            ->toScopes($this->scopes())
            ->withCount('orders')
            ->paginate();
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
