<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdatePurchasingCommodityRequest;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use App\Transformers\TraderOrderTransformer;
use Illuminate\Http\JsonResponse;

class UpdatePurchasingCommodity extends Controller
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
        UpdatePurchasingCommodityRequest $updatePurchasingCommodityRequest,
        UpdateTraderOrder $updateTraderOrder,
        Company $lender,
        FinancingOrder $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        $trader = Trader::driver($traderOrder->provider);

        if (! is_null($updatePurchasingCommodityRequest->file('ptp_document'))) {
            $this->attachDocumentToOrder(
                $traderOrder,
                base64_encode(file_get_contents($updatePurchasingCommodityRequest->file('ptp_document'))),
                FinancingOrderMediaCollection::PromiseToPurchase,
                'base64'
            );
        }

        if (! is_null($updatePurchasingCommodityRequest->file('original_holding_certificate'))) {
            $this->attachDocumentToOrder(
                $traderOrder,
                base64_encode(file_get_contents($updatePurchasingCommodityRequest->file('original_holding_certificate'))),
                FinancingOrderMediaCollection::TtiHoldingCertificate,
                'base64'
            );
        }

        if ($updatePurchasingCommodityRequest->auto_generate_financing_institution_certificate) {
            $trader->createTransferOwnershipToLenderDocument($traderOrder);
        } else {
            if (! is_null($updatePurchasingCommodityRequest->file('financing_institution_certificate'))) {
                $this->attachDocumentToOrder(
                    $traderOrder,
                    base64_encode(file_get_contents($updatePurchasingCommodityRequest->file('financing_institution_certificate'))),
                    FinancingOrderMediaCollection::TransferOwnershipToLender,
                    'base64'
                );
            }
        }

        $data = $updateTraderOrder->handle($traderOrder, $updatePurchasingCommodityRequest->all());

        if ($order->status->canMoveTo(FinancingOrderStatus::CommodityPurchased)) {
            $trader = Trader::driver($traderOrder->provider);

            $trader->updateOrderStatus($order, FinancingOrderStatus::CommodityPurchased);

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::CommodityPurchased
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

        return fractal($data, new TraderOrderTransformer())
            ->parseIncludes(
                'purchasing_commodity_information',
            )
            ->respond();
    }
}
