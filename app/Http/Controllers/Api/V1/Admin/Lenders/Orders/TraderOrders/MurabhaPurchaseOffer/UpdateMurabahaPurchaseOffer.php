<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\MurabhaPurchaseOffer;

use App\Actions\Contracts\Orders\TraderOrders\MurabhaPurchaseOffer\HandleMurabhaPurchaseOffer;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdateMurabahaPurchaseOfferRequest;
use App\Models\TraderOrder;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateMurabahaPurchaseOffer extends Controller
{
    use TraderHelperTrait;

    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    public function __invoke(
        UpdateMurabahaPurchaseOfferRequest $request,
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            app(HandleMurabhaPurchaseOffer::class)->handle($request, $order, $traderOrder);

            return $this->successResponse();
        });
    }
}
