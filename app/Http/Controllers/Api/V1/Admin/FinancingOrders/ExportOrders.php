<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exports\FinancingOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Lender;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as MaatwebsiteExcel;
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
            'activeTraderOrder' => fn ($query) => $query->latest(),
            'lender' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
            'responsableAdmin' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
            'creator' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
        ])
            ->handle();

        $export = (new FinancingOrdersExport($request, $query))
            ->setExcludes(
                $request->boolean('detailed')
                    ? ['reference_number']
                    : ['reference_number', 'national_id', 'selling_price', 'cost_with_vat', 'cost_without_vat']
            );

        return Excel::download($export, $this->getFileName($request, 'csv'), MaatwebsiteExcel::CSV, [
            'X-File-Name' => $this->getFileName($request, 'csv'),
        ]);
    }

    protected function getFileName(Request $request, string $type = 'xlsx')
    {

        $today = saudi_now('Ymd_His');
        $company = $this->getFirstCompany($request);

        return $company
            ? "{$company->name}_LYNKOrderList_{$today}.{$type}"
            : "LYNKOrderList_{$today}.{$type}";
    }

    protected function getFirstCompany(Request $request)
    {
        $company = $request->company;
        $company = is_array($company) ? $company : explode(',', $company);
        if (count($company) !== 1) {
            return null;
        }

        return Lender::find($company[0], ['name']);
    }
}
