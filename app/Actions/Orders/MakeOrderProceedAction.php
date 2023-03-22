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
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

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
     * @return array
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function handle(TraderOrder $traderOrder, string $case, bool $forceToProceed = false)
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
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws \Exception
     */
    protected function handleClientWakalaAccepted(TraderOrder $traderOrder, bool $forceToProceed)
    {
        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        if (
            $this->isPreviousStepOfClientWakalaNotCompleted($traderOrder)
            || ($forceToProceed === false && $order->is_verification_required)
            || ($forceToProceed === false && $this->isClientWakalaStepCompleted($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        if ($this->signedClientWakala) {
            $traderOrder->addMedia($this->signedClientWakala)
                ->toMediaCollection(TraderOrderMediaCollection::SignedClientWakala);
        }

        app(AcceptClientWakala::class)->handle($traderOrder);

        return [];
    }

    protected function isPreviousStepOfClientWakalaNotCompleted(TraderOrder $traderOrder)
    {
        return ! $traderOrder->checkOrderStepComplete(
            $this->stepHistoriesDictionary->getPreviousStepOf(MurabhaStep::ClientWakalaCompleted)->step
        );
    }

    protected function isClientWakalaStepCompleted(TraderOrder $traderOrder)
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakalaCompleted);
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
            $this->isPreviousStepOfContractSignedNotCompleted($traderOrder)
            || ($forceToProceed === false && $this->isContractSignedStepCompleted($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

        return [];
    }

    protected function isPreviousStepOfContractSignedNotCompleted(TraderOrder $traderOrder)
    {
        return ! $traderOrder->checkOrderStepComplete(
            $this->stepHistoriesDictionary->getPreviousStepOf(MurabhaStep::ContractSigned)->step
        );
    }

    protected function isContractSignedStepCompleted(TraderOrder $traderOrder)
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned);
    }

    public function setSignedClientWakala(UploadedFile $signedClientWakala)
    {
        $this->signedClientWakala = $signedClientWakala;

        return $this;
    }
}
