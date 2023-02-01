<?php

namespace App\Http\Controllers\Api\V1\Trader\TraderOrders;

use App\Actions\Contracts\Orders\GetOrder;
use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Orders\UpdatePurchasingCommodityRequest;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use App\Transformers\TraderOrderTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdatePurchasingCommodity extends Controller
{
    use TraderHelperTrait;

    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Trader, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    public function __invoke(
        UpdatePurchasingCommodityRequest $request,
        UpdateTraderOrder $updateTraderOrder,
        GetOrder $getOrder,
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($getOrder, $order, $traderOrder, $request, $updateTraderOrder) {
            $order = $getOrder->setCompany(tenant())->handle($order);

            if ($order->status->cantMoveTo(FinancingOrderStatus::CommodityPurchased)) {
                throw new OrderStatusDoesNotFollowSequenceException();
            }

            $trader = Trader::driver($traderOrder->provider);

            $data = $updateTraderOrder->handle($traderOrder, $request->validated());

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::RespondPtp
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetPtpDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                base64_encode(file_get_contents($request->file('ptp_document'))),
                TraderOrderMediaCollection::PromiseToPurchase,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachPtpDocumentToOrder
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetTtiHoldingCertificateDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                base64_encode(file_get_contents($request->file('original_holding_certificate'))),
                TraderOrderMediaCollection::TtiHoldingCertificate,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument
            );

            if ($request->auto_generate_financing_institution_certificate) {
                $trader->createTransferOwnershipToLenderDocument($traderOrder);
            } else {
                $this->attachDocumentToOrder(
                    $traderOrder,
                    base64_encode(file_get_contents($request->file('financing_institution_certificate'))),
                    TraderOrderMediaCollection::TransferOwnershipToLender,
                    'base64'
                );

                $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateTransferOwnershipToLenderDocument);
            }

            $trader->updateOrderStatus($order, FinancingOrderStatus::CommodityPurchased);

            return fractal($data, new TraderOrderTransformer())
                ->parseIncludes(
                    'purchasing_commodity_information',
                )
                ->respond();
        });
    }
}
