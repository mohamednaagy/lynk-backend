<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\UploadedFile;

class MakeOrderProceedAction implements MakeOrderProceed
{
    use TraderHelperTrait;

    protected ?UploadedFile $signedClientWakala = null;

    /**
     * @param  TraderOrder  $traderOrder
     * @param  string  $case
     * @param  bool  $forceToProceed
     * @return mixed
     *
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function handle(TraderOrder $traderOrder, string $case, $forceToProceed = false)
    {
        return match ($case) {
            FinancingOrderProceedCase::ClientWakalaAccepted => $this->handleClientWakalaAccepted($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::ContractSigned => $this->handleContractSigned($traderOrder, $forceToProceed),
            default => []
        };
    }

    /**
     * @param  TraderOrder  $traderOrder
     * @param  bool  $forceToProceed
     * @return array
     *
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    protected function handleClientWakalaAccepted(TraderOrder $traderOrder, bool $forceToProceed)
    {
        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            FinancingOrderStatus::ClientWakalaCompleted
        );

        if (
            $this->isNotFollowingSequenceForClientWakalaAccepted($traderOrder)
            || ($forceToProceed === false && $order->is_verification_required)
            || ($forceToProceed === false && ! $canUpdateOrderStatus)
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        if ($this->signedClientWakala) {
            $traderOrder->addMedia($this->signedClientWakala)
                ->toMediaCollection(TraderOrderMediaCollection::SignedClientWakala);
        }

        app(AcceptClientWakala::class)->handle($traderOrder);

        if ($canUpdateOrderStatus) {
            $order->update([
                'status' => FinancingOrderStatus::ClientWakalaCompleted,
            ]);
        }
    }

    protected function isNotFollowingSequenceForClientWakalaAccepted($traderOrder)
    {
        return ! $traderOrder->checkOrderStepComplete(FinancingOrderStatus::ContractSigned);
    }

    /**
     * @param  TraderOrder  $traderOrder
     * @param  bool  $forceToProceed
     * @return array
     *
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    protected function handleContractSigned(TraderOrder $traderOrder, bool $forceToProceed)
    {
        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            FinancingOrderStatus::ContractSigned
        );

        if (
            $this->isNotFollowingSequenceForContractSigned($traderOrder)
            || ($forceToProceed === false && ! $canUpdateOrderStatus)
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

        if ($canUpdateOrderStatus) {
            $order->update([
                'status' => FinancingOrderStatus::ContractSigned,
            ]);
        }

        return [];
    }

    protected function isNotFollowingSequenceForContractSigned($traderOrder)
    {
        return ! $traderOrder->checkOrderStepComplete(FinancingOrderStatus::CommodityPurchased);
    }

    public function setSignedClientWakala(UploadedFile $signedClientWakala)
    {
        $this->signedClientWakala = $signedClientWakala;

        return $this;
    }
}
