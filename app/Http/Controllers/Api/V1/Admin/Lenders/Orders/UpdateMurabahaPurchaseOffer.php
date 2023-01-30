<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateMurabahaPurchaseOffer extends Controller
{
    use TraderHelperTrait;

    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Manage])
        );
    }

    public function __invoke(
        Request $request,
        Company $lender,
        FinancingOrder $order
    ): JsonResponse {
        $traderOrder = $order->activeTraderOrder->firstOrFail();

        $this->attachDocumentToOrder(
            $traderOrder,
            base64_encode(file_get_contents($request->file('document'))),
            FinancingOrderMediaCollection::MurabahaPurchaseOrder,
            'base64'
        );

        if ($order->status->is(FinancingOrderStatus::MurabhaOfferIssued)) {
            $trader = Trader::driver($traderOrder->provider);

            $trader->updateOrderStatus($order, FinancingOrderStatus::MurabahaSaleCompleted);

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::MurabahaSaleCompleted
            );

            $warrantDocument = $trader->getDocumentByTypeAndTransaction(
                $traderOrder->reference,
                'Warrant Amendment Except Warrant No'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                $warrantDocument,
                FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument
            );
        }

        return $this->successResponse();
    }
}
