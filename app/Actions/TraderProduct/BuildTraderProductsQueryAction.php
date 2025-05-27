<?php

namespace App\Actions\TraderProduct;

use App\Actions\Contracts\TraderProduct\BuildTraderProductsQuery;
use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use App\Models\TraderProduct;
use Illuminate\Database\Eloquent\Builder;

class BuildTraderProductsQueryAction implements BuildTraderProductsQuery
{
    private $query;

    public function __construct()
    {
        $this->query = TraderProduct::query();
    }

    public function setStatus(?TraderProductStatus $status): self
    {
        $this->query->where('status', $status);

        return $this;
    }

    public function setProvider(?Trader $provider): self
    {
        $this->query->where('provider', $provider);

        return $this;
    }

    public function handle(): Builder
    {
        return $this->query->orderBy('order');
    }
}
