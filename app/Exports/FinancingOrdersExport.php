<?php

namespace App\Exports;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
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
        'reference_number' => 'Reference Number',
        'amount' => 'Commodity Price (SAR)',
        'selling_price' => 'Selling Price (SAR)',
        'national_id' => 'National ID / Iqama',
        'order_owner' => 'Order Owner',
        'company_name' => 'Company Name',
        'status' => 'Status',
        'created_at' => 'Created Date',
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

    /**
     * @param  FinancingOrder  $order
     */
    public function map($order): array
    {
        $items = $this->filterExcludes([
            'id' => fn () => $order->id,
            'reference_number' => fn () => $order->reference_number,
            'amount' => fn () => $order->amount->formatByDecimal(),
            'selling_price' => fn () => $order->selling_price->formatByDecimal(),
            'national_id' => fn () => $order->national_id,
            'order_owner' => fn () => $order->creator->full_name,
            'company_name' => fn () => $order->company->name,
            'status' => fn () => $this->withLocale('en', function () use ($order) {
                return $order->status->isNot(FinancingOrderStatus::InProgress)
                || is_null($order->current_step)
                    ? $order->status->description : $order->current_step->description;
            }),
            'created_at' => fn () => $order->created_at->tz('Asia/Riyadh')->format('Y-m-d H:i:s'),
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
