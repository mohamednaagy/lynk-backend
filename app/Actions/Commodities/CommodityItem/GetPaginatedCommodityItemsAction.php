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
    private string $direction = 'asc';
    private string $sort = 'id';

    public function handle(): LengthAwarePaginator
    {
        return CommodityItem::query()->when($this->uniqueName, function ($query) {
            $query->where('unique_name', $this->uniqueName);
        })->when($this->name, function ($query) {
            $query->where('name', 'like', "%{$this->name}%");
        })->when($this->suppliers, function ($query) {
            $query->whereIn('company_id', $this->suppliers);
        })->when($this->commodityTypes, function ($query) {
            $query->whereIn('commodity_type_id', $this->commodityTypes);
        })->orderBy($this->sort, $this->direction)->paginate();
    }

    /**
     * @param string|null $uniqueName
     * @return $this
     *
     * Set the unique name to filter by.
     */
    public function setuniqueName(?string $uniqueName): self
    {
        $this->uniqueName = $uniqueName;
        return $this;
    }

    /**
     * Set the name to filter by.
     *
     * @param string|null $name
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
     * @param array|null $suppliers
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
     * @param array|null $commodityTypes an array of commodity type ids or null
     * @return $this
     */
    public function setCommodityTypes(?array $commodityTypes): self
    {
        $this->commodityTypes = $commodityTypes;
        return $this;
    }


    /**
     * Set the direction of the sort.
     *
     * @param string|null $direction The direction of the sort. Options are 'asc' or 'desc'.
     * @return $this
     */
    public function setDirection(?string $direction = 'asc'): self
    {
        $this->direction = $direction;
        return $this;
    }

    /**
     * Set the sort field.
     *
     * @param string|null $sort The field to sort by.
     * @return $this
     */
    public function setSort(?string $sort = 'id'): self
    {
        $this->sort = $sort;
        return $this;
    }
}
