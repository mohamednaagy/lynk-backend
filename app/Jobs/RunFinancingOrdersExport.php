<?php

namespace App\Jobs;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Exports\FinancingOrdersExport;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as MaatwebsiteExcel;
use Maatwebsite\Excel\Facades\Excel;

class RunFinancingOrdersExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $timeout = 3600;

    public function __construct(
        public array $requestData,
        public User $user,
        public string $filePath,
        public bool $detailed = false
    ) {
        $this->onQueue('exports');
    }

    public function handle(BuildFinancingOrdersQuery $buildOrdersQuery)
    {
        $query = $buildOrdersQuery->setRelations([
            'activeTraderOrder' => fn ($q) => $q->latest(),
            'company' => fn ($q) => $q->withoutGlobalScope(SoftDeletingScope::class),
            'responsableAdmin' => fn ($q) => $q->withoutGlobalScope(SoftDeletingScope::class),
            'creator' => fn ($q) => $q->withoutGlobalScope(SoftDeletingScope::class),
        ])->handle();

        $export = (new FinancingOrdersExport(
            new \Illuminate\Http\Request($this->requestData),
            $query
        ))->setExcludes(
            $this->detailed
                ? ['reference_number']
                : ['reference_number', 'national_id', 'selling_price', 'cost_with_vat', 'cost_without_vat']
        );

        Excel::store($export, $this->filePath, null, MaatwebsiteExcel::CSV);

        $this->user->notify(new ExportReadyNotification(
            Storage::url($this->filePath)
        ));
    }
}
