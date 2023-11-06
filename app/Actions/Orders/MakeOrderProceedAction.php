<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderRequiresClientVerification;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Jobs\General\ProcessProceedContractAndClientWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class MakeOrderProceedAction implements MakeOrderProceed
{
    use TraderHelperTrait;

    protected ?UploadedFile $signedClientWakala = null;

    /**
     * @return array
     *
     * @throws BindingResolutionException
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function handle(TraderOrder $traderOrder, string $case, bool $forceToProceed = false)
    {
        return match ($case) {
            FinancingOrderProceedCase::ClientWakalaAccepted => $this->handleClientWakalaAccepted($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::ContractSigned => $this->handleContractSigned($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::ContractAndClientWakalaCompleted => $this->handleProceedContractAndClientWakala($traderOrder),
            default => []
        };
    }

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws \Exception
     */
    protected function handleClientWakalaAccepted(TraderOrder $traderOrder, bool $forceToProceed): array
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

    protected function isPreviousStepOfClientWakalaNotCompleted(TraderOrder $traderOrder): bool
    {
        $previousStep = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
            ->getPreviousStepOf(MurabhaStep::ClientWakala)->step;

        return ! $traderOrder->checkOrderStepComplete($previousStep);
    }

    protected function isClientWakalaStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakala);
    }

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws BindingResolutionException
     */
    protected function handleContractSigned(TraderOrder $traderOrder, bool $forceToProceed): array
    {
        if (
            $this->isPreviousStepOfContractSignedNotCompleted($traderOrder)
            || ($forceToProceed === false && $this->isContractSignedStepCompleted($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

        if (! $traderOrder->hasMedia(TraderOrderMediaCollection::ClientWakala)) {
            app()->make(GenerateClientWakala::class)->handle($traderOrder);
        }

        return [];
    }

    protected function isPreviousStepOfContractSignedNotCompleted(TraderOrder $traderOrder): bool
    {
        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
                ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
        );
    }

    protected function isPreviousStepOfContractAndClientWakalaNotCompleted(TraderOrder $traderOrder): bool
    {
        $murabhaSteps = array_keys(get_murabha_steps($traderOrder->provider, $traderOrder->version));

        $firstStepIndex = min(collect([MurabhaStep::ClientWakala, MurabhaStep::ContractSigned])->map(fn ($step) => array_search($step, $murabhaSteps))->toArray());

        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
                ->getPreviousStepOf($murabhaSteps[$firstStepIndex])->step
        );
    }

    protected function isContractSignedStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned);
    }

    /**
     * @return $this
     */
    public function setSignedClientWakala(UploadedFile $signedClientWakala): static
    {
        $this->signedClientWakala = $signedClientWakala;

        return $this;
    }

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws OrderRequiresClientVerification
     */
    protected function handleProceedContractAndClientWakala(TraderOrder $traderOrder): array
    {
        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        if ($order->is_verification_required) {
            throw new OrderRequiresClientVerification;
        }

        $lastHistory = $traderOrder->traderHistories()->latest('id')->first();

        if ($this->isPreviousStepOfContractAndClientWakalaNotCompleted($traderOrder) || is_null($lastHistory)) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        ProcessProceedContractAndClientWakala::dispatchSync($traderOrder->id);

        return [];
    }
}
