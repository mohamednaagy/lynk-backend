<?php

namespace App\Actions\Contracts\Companies\LenderClients;

use App\Models\Lender;
use Illuminate\Database\Eloquent\Builder;

interface GetPaginatedLenderClients
{
    public function handle(): Builder;

    public function setLender(Lender $lender): self;

    public function setName(?string $name): self;

    public function setType(?string $type): self;

    public function setNationalId(?string $nationalId): self;
}
