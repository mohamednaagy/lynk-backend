<?php

namespace App\Exports;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Localizable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancingOrdersExport implements FromGenerator, WithCustomChunkSize, WithHeadings
{
    use Localizable;

    protected array $headings = [
        'id' => 'ID',
        'reference_number' => 'Reference Number',
        'created_date' => 'Created Date',
        'created_time' => 'Created Time',
        'national_id' => 'National ID / Iqama',
        'amount' => 'Commodity Price (SAR)',
        'selling_price' => 'Selling Price (SAR)',
        'charged_transactions' => 'Charged Transactions',
        'order_owner' => 'Order Owner',
        'company_name' => 'Company Name',
        'assigned_to' => 'Assigned To',
        'latest_activity' => 'Latest Activity',
        'cost_with_vat' => 'Cost With Vat (SAR)',
        'cost_without_vat' => 'Cost Without Vat (SAR)',
    ];

    protected array $excludes = [];

    public function __construct(
        protected Request $request,
        protected Builder $ordersQuery
    ) {}

    public function chunkSize(): int
    {
        return 10000;
    }

    public function setExcludes(array $excludes): static
    {
        $this->excludes = $excludes;

        return $this;
    }

    public function headings(): array
    {
        return $this->filterExcludes($this->headings);
    }

    public function generator(): \Generator
    {
        $query = $this->ordersQuery->clone();

        foreach ($query->lazyByIdDesc($this->chunkSize()) as $order) {
            yield $this->map($order);
        }
    }

    protected function map(FinancingOrder $order): array
    {
        $items = $this->filterExcludes([
            'id' => $order->id,
            'reference_number' => $order->reference_number,
            'created_date' => $order->created_at->format('Y-m-d'),
            'created_time' => $order->created_at->format('H:i:s'),
            'national_id' => $order->national_id,
            'amount' => round($order->amount->formatByDecimal(), 2),
            'selling_price' => round($order->selling_price->formatByDecimal(), 2),
            'charged_transactions' => (string) $order->charged_trader_orders_count,
            'order_owner' => $order->creator?->full_name,
            'company_name' => $order->company->name ?? null,
            'assigned_to' => $order->responsableAdmin?->full_name,
            'latest_activity' => $this->withLocale('en', function () use ($order) {
                return $order->status->isNot(FinancingOrderStatus::InProgress)
                || is_null($order->current_step)
                ? $order->status->description
                : $order->current_step->description;
            }),
            'cost_with_vat' => number_format($order->cost_with_vat / 10000, 2),
            'cost_without_vat' => number_format($order->cost_without_vat / 10000, 2),
        ]);

        return array_values($items);
    }

    protected function filterExcludes(array $items): array
    {
        return array_filter(
            $items,
            fn ($value, $key) => ! in_array($key, $this->excludes, true),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
