<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Lenders\GetValidCommodityTypes;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetValidCommodityType extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::CommodityMarketCommodityTypes, Action::Index, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, GetValidCommodityTypes $getValidCommodityTypes)
    {
        $company = $request->user()->company;
        $commodities = $getValidCommodityTypes->handle($company);

        if (empty($commodities)) {
            return $this->errorResponse('This action is not allowed for this company.', 400);
        }

        return $this->successResponse($commodities);
    }
}
