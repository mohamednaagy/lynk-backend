<?php

namespace App\Actions\Companies\LenderClients;

use App\Actions\Contracts\Companies\LenderClients\GetPaginatedLenderClients;
use App\Models\CompanyLenderClient;
use App\Models\Lender;
use Illuminate\Database\Eloquent\Builder;

class GetPaginatedLenderClientsAction implements GetPaginatedLenderClients
{
    private Builder $query;

    public function __construct()
    {
        $this->query = CompanyLenderClient::query();
    }

    public function setLender(Lender $lender): self
    {
        $this->query->where('company_id', $lender->id);

        return $this;
    }

    public function handle(): Builder
    {
        return $this->query->orderBy('id', 'desc');
    }

    public function setName(?string $name): self
    {
        if (filled($name)) {
            $this->query->where('name', 'like', "%{$name}%");
        }

        return $this;
    }

    public function setType(?string $type): self
    {
        if (filled($type)) {
            $typeIds = explode(',', $type);
            $this->query->whereIn('type', $typeIds);
        }

        return $this;
    }

    public function setNationalId(?string $nationalId): self
    {
        if (filled($nationalId)) {
            $this->query->where('national_id', 'like', "%{$nationalId}%");
        }

        return $this;
    }
}
