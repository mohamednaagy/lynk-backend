<?php

namespace App\Http\Controllers\Api\V1\Supplier\Location;

use App\Actions\Contracts\Commodities\CommodityLocation\BuildSupplierLocationsQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Transformers\SupplierLocationsTransformer;
use Illuminate\Http\JsonResponse;

class CommodityLocationLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierLocations, Action::Manage, Action::Index])
        );
    }

    /**
     * Handle the incoming request to list commodity supplier locations.
     */
    public function __invoke(
        BuildSupplierLocationsQuery $buildSupplierLocationsQuery
    ): JsonResponse {
        $locations = $buildSupplierLocationsQuery->setSupplier(tenant()->supplier)->handle()->get(['id', 'name', 'unique_identifier']);

        return fractal($locations, new SupplierLocationsTransformer)
            ->parseIncludes(['id', 'name', 'unique_identifier'])
            ->respond();
    }
}
