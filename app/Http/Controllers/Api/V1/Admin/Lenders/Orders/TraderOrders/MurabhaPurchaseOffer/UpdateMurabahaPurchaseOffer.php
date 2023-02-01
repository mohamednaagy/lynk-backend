<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\MurabhaPurchaseOffer;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdateMurabahaPurchaseOfferRequest;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
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
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    public function __invoke(
        UpdateMurabahaPurchaseOfferRequest $request,
        Company $lender,
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            $trader = Trader::driver($traderOrder->provider);

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::IssueMurabahaOffer
            );

            $trader->updateOrderStatus($order, FinancingOrderStatus::MurabhaOfferIssued);

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetMurabahaPurchaseOfferDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                base64_encode(file_get_contents($request->file('document'))),
                TraderOrderMediaCollection::MurabahaPurchaseOrder,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachMpoDocument
            );

            return $this->successResponse();
        });
    }
}
