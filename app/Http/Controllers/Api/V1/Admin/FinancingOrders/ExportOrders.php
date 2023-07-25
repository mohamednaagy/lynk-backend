<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Exports\FinancingOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportOrders extends Controller
{
    public function __invoke(Request $request, BuildFinancingOrdersQuery $buildOrdersQuery)
    {
        $company = Company::find($request->input('company'));

        if ($company) {
            $buildOrdersQuery->setCompany($company);
        }

        $query = $buildOrdersQuery->setRelations([
            'activeTraderOrder' => fn ($query) => $query->withLastHistoryAction()->latest(),
            'company' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
            'creator',
        ])
            ->handle();

        $export = (new FinancingOrdersExport($request, $query));

        return Excel::download($export, $this->getFileName());
    }

    protected function getFileName()
    {
        $todayDateInYYYYMMDD = now('Asia/Riyadh')->format('Ymd_His');

        return "LYNKOrderList_{$todayDateInYYYYMMDD}.xlsx";
    }
}
