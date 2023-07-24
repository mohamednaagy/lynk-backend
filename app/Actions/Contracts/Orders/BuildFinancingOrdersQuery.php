<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface BuildFinancingOrdersQuery
{
    public function handle(): Builder;

    public function setCreator(Model $creator): self;

    public function setCompany(Company $company): self;

    public function setRelations(array $relations): self;
}
