<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetPaginatedCompanies;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedCompaniesAction implements GetPaginatedCompanies
{
    public $type = null;

    public function handle(): LengthAwarePaginator
    {
        return Company::query()
            ->when($this->type, function ($query) {
                $query->traderType($this->type);
            })
            ->withCount('orders')->paginate();
    }

    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }
}
