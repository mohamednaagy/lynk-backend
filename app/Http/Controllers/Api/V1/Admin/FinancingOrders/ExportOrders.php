<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Jobs\RunFinancingOrdersExport;
use App\Models\Company;
use Illuminate\Http\Request;

class ExportOrders extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Index])
        );
    }

    public function __invoke(Request $request)
    {
        $fileName = $this->getFileName($request, 'csv');
        $filePath = "exports/{$fileName}";

        RunFinancingOrdersExport::dispatch(
            $request->all(),
            $request->user(),
            $filePath,
            $request->boolean('detailed')
        );

        return response()->json([
            'status' => 'queued',
            'message' => 'Your export has been queued and will be available soon.',
            'file' => $fileName,
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

        return Company::find($company[0], ['name']);
    }
}
