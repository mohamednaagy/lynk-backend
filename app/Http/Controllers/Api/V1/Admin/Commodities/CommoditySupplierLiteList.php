<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildPaginatedCommoditySuppliersQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\CommoditySuppliersLiteListRequest;
use App\Transformers\CommoditySuppliersTransformer;
use Illuminate\Http\JsonResponse;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
class CommoditySupplierLiteList extends Controller
{
    public function __construct() {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketSuppliers, Action::Index, Action::Manage])
        );
    }

    /**
     * Handle the incoming request to list commodity suppliers.
     *
     * @param BuildPaginatedCommoditySuppliersQuery $buildPaginatedCommoditySuppliersQuery
     * @return JsonResponse
     */
    public function __invoke(CommoditySuppliersLiteListRequest $request, BuildPaginatedCommoditySuppliersQuery $buildPaginatedCommoditySuppliersQuery): JsonResponse
    {
        $commiditySuppliers = $buildPaginatedCommoditySuppliersQuery
            ->setType(CompanyType::Supplier)
            ->setName($request->validated('search'))
            ->handle()
            ->get(['id', 'name']);

        return fractal($commiditySuppliers, new CommoditySuppliersTransformer())
            ->parseIncludes(['id', 'legal_name'])
            ->respond();
    }
}
