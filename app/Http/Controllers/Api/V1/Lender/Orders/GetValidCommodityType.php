<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Lenders\GetValidCommodityTypes;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetValidCommodityType extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::CommodityMarketCommodityTypes, Action::Index])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, GetValidCommodityTypes $getValidCommodityTypes)
    {
        $user = $request->user();
        $company = $user->company;
        $commodities = $getValidCommodityTypes->handle($company);

        if ($user->hasRole(Role::LenderApiUser) && empty($commodities)) {
            return $this->errorResponse('This action is not allowed for this company.', 400);
        }

        return $this->successResponse($commodities);
    }
}
