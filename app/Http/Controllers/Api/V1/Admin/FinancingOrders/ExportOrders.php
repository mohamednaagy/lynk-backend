<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exports\FinancingOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Company;
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
            'creator' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
        ])
            ->handle();

        $export = (new FinancingOrdersExport($request, $query))->setExcludes(['reference_number']);

        return Excel::download($export, $this->getFileName($request), null, [
            'X-File-Name' => $this->getFileName($request),
        ]);
    }

    protected function getFileName(Request $request)
    {
        $todayDateInYYYYMMDD = now('Asia/Riyadh')->format('Ymd_His');

        $company = $this->getFirstCompany($request);

        if ($company) {
            return "{$company->name}_LYNKOrderList_{$todayDateInYYYYMMDD}.xlsx";
        }

        return "LYNKOrderList_{$todayDateInYYYYMMDD}.xlsx";
    }

    protected function getFirstCompany(Request $request)
    {
        $company = $request->company;
        $company = is_array($company)
            ? $company
            : explode(',', $company);

        if (count($company) !== 1) {
            return null;
        }

        return Company::find($company[0], ['name']);
    }
}
