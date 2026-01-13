<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exports\FinancingOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Lender;
use App\Services\ExportService;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
        BuildFinancingOrdersQuery $buildOrdersQuery,
        ExportService $exportService
    ): JsonResponse {
        $query = $buildOrdersQuery->setRelations([
            'activeTraderOrder' => fn ($query) => $query->latest(),
            'lender' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
            'responsableAdmin' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
            'creator' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
        ])
            ->handle();

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Convert bindings to proper values
        $params = [];
        foreach ($bindings as $binding) {
            $params[] = \is_object($binding) && $binding instanceof \DateTimeInterface ? $binding->format('Y-m-d H:i:s') : $binding;
        }

        // Dispatch the export job to be processed asynchronously
        $exportService->dispatchExportJob(
            sqlQuery: $sql,
            params: $params,
            exportType: 'ORDER_LIST',
            user: $request->user(),
            exportClass: FinancingOrdersExport::class,
            fileName: $this->getFileName($request),
            requestData: $request->all()
        );

        // Return a response indicating the export is being processed
        return response()->json([
            'message' => 'Your export request has been submitted and is being processed.',
            'status' => 'processing',
        ], JsonResponse::HTTP_ACCEPTED);
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
        $company = is_array($company) ? $company : explode(',', $company ?? '');
        if (count($company) !== 1) {
            return null;
        }

        return Lender::find($company[0], ['name']);
    }
}
