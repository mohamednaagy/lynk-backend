<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exports\FinancingOrdersExport;
use App\Http\Controllers\Controller;
use App\Jobs\Reports\Enums\ReportType;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
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

    public function __invoke(
        Request $request,
        ExportService $exportService
    ): JsonResponse {
        // Dispatch the export job to be processed asynchronously
        $exportService->dispatchOrderListExportJob(
            exportType: ReportType::OrderList,
            user: $request->user(),
            exportClass: FinancingOrdersExport::class,
            fileName: $this->getFileName($request),
            requestData: $request->all()
        );

        // Return a response indicating the export is being processed
        return $this->successResponse([
            'message' => __('notification.report-export-processing'),
        ]);
    }

    protected function getFileName(Request $request, string $type = 'xlsx'): string
    {
        $today = saudi_now('Ymd_His');

        return "LYNKOrderList_{$today}.{$type}";
    }
}
