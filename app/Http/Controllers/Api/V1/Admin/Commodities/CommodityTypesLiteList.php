<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommodityItem\CommodityTypesLiteListRequest;
use App\Models\Company;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CommodityTypesLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityTypes, Action::Index, Action::Manage])
        );
    }

    public function __invoke(CommodityTypesLiteListRequest $request, BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypeQuery): JsonResponse
    {
        if ($request->has('company_id')) {
            if (! $this->checkIfCompanyAllowedToSelectPreferredCommodity($request->validated('company_id'))) {
                return $this->respondWithEmptyData();
            }
        }

        $commodityTypes = $buildPaginatedCommodityTypeQuery
            ->setName($request->validated('search'))
            ->setStatus($request->validated('status'))
            ->setProvider($request->validated('provider'))
            ->setCompanyId($request->validated('company_id'))
            ->handle()
            ->get(['id', 'name', 'provider']);

        return fractal($commodityTypes, new CommodityTypeTransformer)
            ->parseIncludes(['id', 'name', 'provider'])
            ->respond();
    }

    private function checkIfCompanyAllowedToSelectPreferredCommodity(int $companyId)
    {
        $company = Company::with('lender.lenderDetail')->find($companyId);

        return $company->lender->lenderDetail->allow_preferred_commodity_in_order;
    }

    private function respondWithEmptyData(): JsonResponse
    {
        $response = ['data' => []];

        return response()->json($response, Response::HTTP_OK);
    }
}
