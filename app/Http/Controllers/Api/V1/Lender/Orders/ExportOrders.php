<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Exports\FinancingOrdersExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportOrders extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::FinancingOrders, Action::Manage, Action::Index])
        );
    }

    public function __invoke(Request $request, BuildFinancingOrdersQuery $buildOrdersQuery)
    {
        if ($request->user()->hasRole(Role::LenderOrderCreator)) {
            $buildOrdersQuery->setCreator($request->user());
        }

        $query = $buildOrdersQuery->setCompany(tenant())
            ->setRelations([
                'activeTraderOrder' => fn ($query) => $query->withLastHistoryAction()->latest(),
            ])
            ->handle();

        $export = (new FinancingOrdersExport($request, $query))->setExcludes(['company_name']);

        return Excel::download($export, $this->getFileName());
    }

    protected function getFileName()
    {
        $companyName = tenant()->name;
        $todayDateInYYYYMMDD = now('Asia/Riyadh')->format('Ymd_His');

        return "{$companyName}_LYNKOrderList_{$todayDateInYYYYMMDD}.xlsx";
    }
}
