<?php

namespace App\Actions\Commodities\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Models\CommodityType;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCommodityTypeQueryAction implements BuildPaginatedCommodityTypeQuery
{
    private $status;

    private ?string $name = null;

    private ?string $uniqueName = null;

    private ?int $active = null;

    private array $providers = [];

    private ?int $companyId = null;

    public function handle(): Builder
    {
        return CommodityType::with('statistics')
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })->when($this->name, function ($query) {
                $query->where('name', 'like', "%{$this->name}%");
            })->when($this->uniqueName, function ($query) {
                $query->where('unique_name', $this->uniqueName);
            })->when($this->providers, function ($query) {
                $query->whereIn('provider', $this->providers);
            })->when($this->active, function ($query) {
                $query->active($this->active);
            })->when($this->companyId, function ($query) {
                $query->getCommoditiesBasedOnCompany(($this->companyId));
            });
    }

    public function setStatus($status = null): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param  string|null  $name  The name of the commodity type.
     * @return $this
     */
    public function setName(?string $name = null): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUniqueName(?string $uniqueName = null): self
    {
        $this->uniqueName = $uniqueName;

        return $this;
    }

    /**
     * Set the active filter status.
     *
     * @param  int|null  $value  The active filter value:
     *                           1 = Active
     *                           2 = Inactive
     *                           3/null = All
     * @return $this
     */
    public function setActive(?int $value): self
    {
        $this->active = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function setProvider(?string $providers = null): self
    {
        $this->providers = $providers ? array_map('trim', explode(',', $providers)) : [];

        return $this;
    }

    /**
     * @return $this
     */
    public function setCompanyId(?int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }
}
