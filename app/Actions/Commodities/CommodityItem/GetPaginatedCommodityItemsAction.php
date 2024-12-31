<?php

namespace App\Actions\Commodities\CommodityItem;

use App\Actions\Contracts\Commodities\CommodityItem\GetPaginatedCommodityItems;
use App\Models\CommodityItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedCommodityItemsAction implements GetPaginatedCommodityItems
{
    protected ?string $uniqueName;

    protected ?string $name = null;

    protected array $suppliers = [];

    protected array $commodityTypes = [];

    private ?int $active = 1; // Default to "active" items (active = 1)

    private ?string $direction = 'asc';

    private ?string $sort = 'id';

    public function handle(): LengthAwarePaginator
    {
        return CommodityItem::query()
            ->when($this->uniqueName, function ($query) {
                $query->where('unique_name', 'like', "%{$this->uniqueName}%");
            })->when($this->name, function ($query) {
                $query->where('name', 'like', "%{$this->name}%");
            })->when($this->suppliers, function ($query) {
                $query->whereIn('company_id', $this->suppliers);
            })->when($this->commodityTypes, function ($query) {
                $query->whereIn('commodity_type_id', $this->commodityTypes);
            })->active($this->active)
            ->orderBy($this->sort, $this->direction)->paginate();
    }

    /**
     * Set the unique name to filter by.
     *
     * @return $this
     */
    public function setuniqueName(?string $uniqueName): self
    {
        $this->uniqueName = $uniqueName;

        return $this;
    }

    /**
     * Set the name to filter by.
     *
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the suppliers to filter by.
     *
     * @return $this
     */
    public function setSuppliers(?array $suppliers): self
    {
        $this->suppliers = $suppliers;

        return $this;
    }

    /**
     * Set the commodity types to filter by.
     *
     * @param  array|null  $commodityTypes  an array of commodity type ids or null
     * @return $this
     */
    public function setCommodityTypes(?array $commodityTypes): self
    {
        $this->commodityTypes = $commodityTypes;

        return $this;
    }

    /**
     * Set the active filter status.
     *
     * @param  int|null  $value  The active filter value:
     *                           1 = Active
     *                           2 = Inactive
     *                           3 = No filter (or null to default to 1).
     * @return $this
     */
    public function setActive(?int $value): self
    {
        $this->active = $value ?? 1; // Default to "active" (1) if not provided

        return $this;
    }

    /**
     * Set the direction of the sort.
     *
     * @param  string|null  $direction  The direction of the sort. Options are 'asc' or 'desc'.
     * @return $this
     */
    public function setDirection(?string $direction = 'asc'): self
    {
        if (is_null($direction)) {
            return $this;
        }
        $this->direction = $direction;

        return $this;
    }

    /**
     * Set the sort field.
     *
     * @param  string|null  $sort  The field to sort by.
     * @return $this
     */
    public function setSort(?string $sort = 'id'): self
    {
        if (is_null($sort)) {
            return $this;
        }
        $this->sort = $sort;

        return $this;
    }
}
