<?php

namespace App\Exports;

use App\Enums\FinancingOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Localizable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinancingOrdersExport implements FromQuery, WithHeadings, WithMapping
{
    use Localizable;

    protected array $headings = [
        'id' => 'ID',
        'amount' => 'Commodity Price (SAR)',
        'selling_price' => 'Selling Price (SAR)',
        'national_id' => 'National ID / Iqama',
        'status' => 'Status',
        'company_name' => 'Company Name',
    ];

    protected $excludes = [];

    public function __construct(protected Request $request, protected Builder $ordersQuery)
    {
    }

    public function query()
    {
        return $this->ordersQuery;
    }

    public function headings(): array
    {
        return $this->filterExcludes($this->headings);
    }

    public function setExcludes($excludes)
    {
        $this->excludes = $excludes;

        return $this;
    }

    public function map($order): array
    {
        $items = $this->filterExcludes([
            'id' => fn () => $order->id,
            'amount' => fn () => $order->amount->formatByDecimal(),
            'selling_price' => fn () => $order->selling_price->formatByDecimal(),
            'national_id' => fn () => $order->national_id,
            'status' => fn () => $this->withLocale('en', function () use ($order) {
                return $order->status->isNot(FinancingOrderStatus::InProgress)
                || is_null($order->current_step)
                    ? $order->status->description : $order->current_step->description;
            }),
            'company_name' => fn () => $order->company->name,
        ]);

        return array_map(fn ($item) => $item(), $items);
    }

    protected function filterExcludes($items)
    {
        return array_values(
            array_filter(
                $items,
                fn ($value, $key) => ! in_array($key, $this->excludes),
                ARRAY_FILTER_USE_BOTH
            )
        );
    }
}
