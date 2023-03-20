<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\UploadedFile;

class MakeOrderProceedAction implements MakeOrderProceed
{
    use TraderHelperTrait;

    protected ?UploadedFile $signedClientWakala = null;

    public function __construct(protected StepHistoriesDictionary $stepHistoriesDictionary)
    {
    }

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

        if (
            $this->isPreviousStepOfClientWakalaNotComplete($traderOrder)
            || ($forceToProceed === false && $order->is_verification_required)
            || ($forceToProceed === false && $this->isClientWakalaStepNotComplete($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        if ($this->signedClientWakala) {
            $traderOrder->addMedia($this->signedClientWakala)
                ->toMediaCollection(TraderOrderMediaCollection::SignedClientWakala);
        }

        app(AcceptClientWakala::class)->handle($traderOrder);
    }

    protected function isPreviousStepOfClientWakalaNotComplete(TraderOrder $traderOrder)
    {
        return ! $traderOrder->checkOrderStepComplete(
            $this->stepHistoriesDictionary->getPreviousStepOf(MurabhaStep::ClientWakala)->step
        );
    }

    protected function isClientWakalaStepNotComplete(TraderOrder $traderOrder)
    {
        return ! $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakala);
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
        if (
            $this->isPreviousStepOfContractSignedNotComplete($traderOrder)
            || ($forceToProceed === false && $this->isContractSignedStepNotComplete($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

        return [];
    }

    protected function isPreviousStepOfContractSignedNotComplete(TraderOrder $traderOrder)
    {
        return ! $traderOrder->checkOrderStepComplete(
            $this->stepHistoriesDictionary->getPreviousStepOf(MurabhaStep::ContractSigned)->step
        );
    }

    protected function isContractSignedStepNotComplete(TraderOrder $traderOrder)
    {
        return ! $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned);
    }

    public function setSignedClientWakala(UploadedFile $signedClientWakala)
    {
        $this->signedClientWakala = $signedClientWakala;

        return $this;
    }
}
