<?php
namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityItem\GetPaginatedCommodityItems;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Transformers\Admin\CommodityItem\CommodityItemsTransformer;
use Illuminate\Http\JsonResponse;

class CommodityItemController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityItems, Action::Index, Action::Manage])
        )->only('index');
        
    }

    public function index(
        GetPaginatedCommodityItems $getPaginatedCommodityItems
    ): JsonResponse {
        $commidityItems = $getPaginatedCommodityItems->handle();
       

        return fractal($commidityItems, new CommodityItemsTransformer())
            ->parseIncludes([
                'id',
                'name',
                'supplier',
                'unique_name',
                'company_id',
                'commodity_type',
                'available_units',
                'reserved_units',
            ])
            ->respond();
    }
}