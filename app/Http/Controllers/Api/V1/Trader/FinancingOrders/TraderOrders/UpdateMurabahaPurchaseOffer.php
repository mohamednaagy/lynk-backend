<?php

namespace App\Http\Controllers\Api\V1\Trader\FinancingOrders\TraderOrders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Actions\Contracts\Orders\TraderOrders\MurabahaPurchaseOffer\HandleIssuingMurabahaPurchaseOffer;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Orders\TraderOrders\UpdateMurabahaPurchaseOfferRequest;
use App\Models\Company;
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
                perm(Area::Trader, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    public function __invoke(
        UpdateMurabahaPurchaseOfferRequest $request,
        Company $lender,
        int $order,
        int $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            [$order, $traderOrder] = app(GetOrderAndTraderOrderLockedForUpdate::class)->handle($traderOrder);

            if (! $traderOrder->client_wakala_accepted_at) {
                throw new OrderStatusDoesNotFollowSequenceException();
            }

            app(HandleIssuingMurabahaPurchaseOffer::class)->handle($request, $order, $traderOrder);

            return $this->successResponse();
        });
    }
}
