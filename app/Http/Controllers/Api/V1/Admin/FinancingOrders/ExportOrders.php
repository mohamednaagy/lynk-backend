<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exports\FinancingOrdersExport;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportOrders extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Index])
        );
    }

    public function __invoke(Request $request, BuildFinancingOrdersQuery $buildOrdersQuery)
    {
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
