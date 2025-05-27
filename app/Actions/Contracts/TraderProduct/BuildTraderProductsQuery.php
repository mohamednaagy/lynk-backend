<?php

namespace App\Actions\Contracts\TraderProduct;

use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use Illuminate\Database\Eloquent\Builder;

interface BuildTraderProductsQuery
{
    public function handle(): Builder;

    public function setStatus(TraderProductStatus $status): self;

    public function setProvider(Trader $provider): self;
}
