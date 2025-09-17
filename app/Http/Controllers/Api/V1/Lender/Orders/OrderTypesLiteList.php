<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Companies\GetOrderType;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CommodityTypeStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderTypesLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.perm(Area::Lender, [Subject::FinancingOrderTypes, Action::Manage, Action::Index])
        );
    }

    /**
     * Handle the incoming request.
     */
    public function index(GetOrderType $getOrderTypes): JsonResponse
    {
        $lender = auth()->user()->lender;
        $OrderTypes = $getOrderTypes
            ->handle($lender);

            return $this->successResponse(['allowed_financing_order_types' => $OrderTypes->toArray()]);
        }
}
