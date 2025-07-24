<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CommodityTypeStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CommodityTypesLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.perm(Area::Lender, [Subject::CommodityMarketCommodityTypes, Action::Index])
        );
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypeQuery): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company->lender?->isPreferredCommoditySelectionAllowed()) {
            return $this->errorResponse('This action is not allowed for this company.', 400);
        }

        $commodities = $buildPaginatedCommodityTypeQuery
            ->setCompanyId($company->id)
            ->setStatus(CommodityTypeStatus::Active)
            ->handle();

        return $this->successResponse(
            $commodities->get(['unique_name', 'name'])
                ->map(
                    fn ($commodity) => [
                        'id' => $commodity->unique_name,
                        'name' => $commodity->name,
                    ]
                )
                ->values()
                ->toArray()
        );
    }
}
