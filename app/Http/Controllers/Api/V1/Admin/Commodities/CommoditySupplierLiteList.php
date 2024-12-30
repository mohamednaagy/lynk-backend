<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildPaginatedCommoditySuppliersQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\CommoditySuppliersLiteListRequest;
use App\Transformers\CommoditySuppliersTransformer;
use Illuminate\Http\JsonResponse;

class CommoditySupplierLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketSuppliers, Action::Index, Action::Manage])
        );
    }

    /**
     * Handle the incoming request to list commodity suppliers.
     */
    public function __invoke(CommoditySuppliersLiteListRequest $request, BuildPaginatedCommoditySuppliersQuery $buildPaginatedCommoditySuppliersQuery): JsonResponse
    {
        $commiditySuppliers = $buildPaginatedCommoditySuppliersQuery
            ->setType(CompanyType::Supplier)
            ->setName($request->validated('search'))
            ->setStatus($request->validated('status'))
            ->handle()
            ->get(['id', 'name']);

        return fractal($commiditySuppliers, new CommoditySuppliersTransformer)
            ->parseIncludes(['id', 'legal_name'])
            ->respond();
    }
}
