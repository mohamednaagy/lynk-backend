<?php

namespace App\Actions\Supplier\CommodityItem;

use App\Actions\Contracts\Supplier\CommodityItem\BuildPaginatedCommodityItemQuery;
use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

class BuildPaginatedCommodityItemQueryAction implements BuildPaginatedCommodityItemQuery
{
    protected ?string $uniqueName;
    protected ?string $name = null;
    protected array $commodityTypes = [];
    protected array $suppliers = []; // Add this line to define the suppliers property
    private string $direction = 'asc';
    private string $sort = 'id';

    public function handle(Supplier|Company $supplier): Builder
    {
        return $supplier->commodityItems()->getQuery()
            ->when($this->uniqueName, function ($query) {
                $query->where('unique_name', 'like', "%{$this->uniqueName}%");
            })
            ->when($this->name, function ($query) {
                $query->where('name', 'like', "%{$this->name}%");
            })
            ->when(!empty($this->commodityTypes), function ($query) {
                $query->whereIn('commodity_type_id', $this->commodityTypes);
            })
            ->orderBy($this->sort ?? 'id', $this->direction ?? 'asc');
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
       if(is_null($direction))
            return $this;
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
        if(is_null($sort))
            return $this;
        $this->sort = $sort;
        return $this;
    }

}
