<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildSupplierMonthlyUsageQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\SupplierMonthlyUsageRequest;
use App\Models\Supplier;
use App\Transformers\SupplierMonthlyUsageTransformer;
use Illuminate\Http\JsonResponse;

class SupplierMonthlyUsage extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommodityMarketSuppliers, Action::Show, Action::Manage])
        );
    }

    public function __invoke(
        SupplierMonthlyUsageRequest $request,
        Supplier $commoditySupplier,
        BuildSupplierMonthlyUsageQuery $getCommoditySupplierReports
    ): JsonResponse {
        $reports = $getCommoditySupplierReports
            ->setSupplier($commoditySupplier)
            ->setType($request->validated('type'))
            ->handle()
            ->paginate();

        return fractal($reports, new SupplierMonthlyUsageTransformer)
            ->parseIncludes(['id', 'created_at', 'file_name', 'download_url'])
            ->respond();
    }
}
