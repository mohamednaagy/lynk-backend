<?php

namespace App\Actions\Contracts\Orders;

use App\Models\Lender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface BuildFinancingOrdersQuery
{
    public function handle(): Builder;

    public function setCreator(Model $creator): self;

    public function setLender(Lender $lender): self;

    public function setRelations(array $relations): self;
}
