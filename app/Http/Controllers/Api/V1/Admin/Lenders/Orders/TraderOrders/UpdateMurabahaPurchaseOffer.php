<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lender\Orders\MurabahaPurchase\UpdateDocumentRequest;
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
        UpdateDocumentRequest $request,
        Company $lender,
        FinancingOrder $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        if ($request->has('document')) {
            $this->attachDocumentToOrder(
                $traderOrder,
                base64_encode(file_get_contents($request->file('document'))),
                FinancingOrderMediaCollection::MurabahaPurchaseOrder,
                'base64'
            );
        }

        if ($order->status->canMoveTo(FinancingOrderStatus::MurabahaSaleCompleted)) {
            $trader = Trader::driver($traderOrder->provider);

            DB::transaction(function () use ($trader, $order, $traderOrder) {
                $trader->updateOrderStatus($order, FinancingOrderStatus::MurabahaSaleCompleted);

                $trader->createTraderOrderHistory(
                    $traderOrder,
                    FinancingOrderHistory::MurabahaSaleCompleted
                );
            });
        }

        return $this->successResponse();
    }
}
