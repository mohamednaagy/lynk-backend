<?php

namespace App\Actions\Contracts\Companies;

use Illuminate\Database\Eloquent\Builder;

interface BuildPaginatedCompaniesQuery
{
    public function handle(): Builder;

    public function setType(string $type): self;
}
