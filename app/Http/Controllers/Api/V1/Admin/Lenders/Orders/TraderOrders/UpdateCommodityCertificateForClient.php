<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Actions\Contracts\Orders\TraderOrders\SellingCommodityToCustomer\HandleSellingCommodityToCustomer;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdateSellingCommodityToClientRequest;
use App\Models\Company;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateCommodityCertificateForClient extends Controller
{
    use TraderHelperTrait;

    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  UpdateSellingCommodityToClientRequest  $request
     * @param  Company  $lender
     * @param  int  $order
     * @param  int  $traderOrder
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function __invoke(
        UpdateSellingCommodityToClientRequest $request,
        Company $lender,
        int $order,
        int $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            [$order, $traderOrder] = app(GetOrderAndTraderOrderLockedForUpdate::class)->handle($traderOrder);

            $traderOrder->ensureCanAccessStep(
                FinancingOrderStatus::ContractSigned
            );

            app(HandleSellingCommodityToCustomer::class)->handle($request, $order, $traderOrder);

            return $this->successResponse([
                'url' => $traderOrder
                    ->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)
                    ?->file_url,
            ]);
        });
    }
}
