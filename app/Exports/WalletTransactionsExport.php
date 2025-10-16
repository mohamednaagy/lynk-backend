<?php

namespace App\Exports;

use App\Models\Company;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Localizable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WalletTransactionsExport implements FromGenerator, WithCustomChunkSize, WithHeadings
{
    use Localizable;

    public function __construct(
        protected Request $request,
        protected Builder $transactionsQuery,
        protected Company $company
    ) {}

    protected array $headings = [
        'date' => 'Transaction Date',
        'transaction_description' => 'Transaction Description',
        'amount' => 'Transaction Amount',
        'balance' => 'Remaining Amount Balance',
    ];

    protected array $excludes = [];

    public function chunkSize(): int
    {
        return 5000;
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
        foreach ($this->transactionsQuery->lazy($this->chunkSize()) as $transaction) {
            yield $this->map($transaction);
        }
    }

    protected function map($transaction): array
    {
        $items = $this->filterExcludes([
            'date' => saudi_now('Y-m-d H:i:s', $transaction->created_at),
            'transaction_description' => $this->withLocale('en', function () use ($transaction) {
                return ! is_null($transaction->reason)
                    ? app(TransactionUtilInterface::class)->getDescription($transaction)
                    : null;
            }),
            'amount' => $transaction->amount?->convertAndFormatByDecimal(separator: ','),
            'balance' => $transaction->balance?->convertAndFormatByDecimal(separator: ','),
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
