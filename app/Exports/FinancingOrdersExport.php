<?php

namespace App\Exports;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Collections\FinancingOrderCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Localizable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinancingOrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Localizable;

    protected array $headings = [
        'id' => 'ID',
        'amount' => 'Commodity Price (SAR)',
        'selling_price' => 'Selling Price (SAR)',
        'reference_number' => 'Reference Number',
        'national_id' => 'National ID / Iqama',
        'order_owner' => 'Order Owner',
        'company_name' => 'Company Name',
        'status' => 'Status',
        'created_date' => 'Created Date',
        'created_time' => 'Created Time',
        'cost_with_vat' => 'Cost With Vat (SAR)',
        'cost_without_vat' => 'Cost Without Vat (SAR)',
    ];

    protected array $excludes = [];

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
            'amount' => fn () => number_format($order->amount->convertAndFormatByDecimal(), 2),
            'selling_price' => fn () => number_format($order->selling_price->convertAndFormatByDecimal(), 2),
            'reference_number' => fn () => $order->reference_number,
            'national_id' => fn () => $order->national_id,
            'order_owner' => fn () => $order->creator?->full_name,
            'company_name' => fn () => $order->company->name,
            'status' => fn () => $this->withLocale('en', function () use ($order) {
                return $order->status->isNot(FinancingOrderStatus::InProgress)
                    || is_null($order->current_step)
                    ? $order->status->description : $order->current_step->description;
            }),
            'created_date' => fn () => $order->created_at->clone()->tz('Asia/Riyadh')->format('Y-m-d'),
            'created_time' => fn () => $order->created_at->clone()->tz('Asia/Riyadh')->format('H:i:s'),
            'cost_with_vat' => fn () => number_format($order->cost_with_vat?->convertAndFormatByDecimal(), 2),
            'cost_without_vat' => fn () => number_format($order->cost_without_vat?->convertAndFormatByDecimal(), 2),
        ]);

        return array_map(fn ($item) => $item(), $items);
    }

    public function prepareRows($orders)
    {
        if ($this->doesCostExistInExcludes()) {
            return $orders;
        }

        return (new FinancingOrderCollection($orders))->loadCost();
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

    public function doesCostExistInExcludes()
    {
        return count(
            array_diff(
                ['cost_with_vat', 'cost_without_vat'],
                $this->excludes
            )
        ) === 0;
    }
}
