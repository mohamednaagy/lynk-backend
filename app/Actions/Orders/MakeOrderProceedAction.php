<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
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
        $clientWakala = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::ClientWakala,
            'bursam' => BursamMurabhaStep::ClientWakala,
        };

        $previousStep = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
            ->getPreviousStepOf($clientWakala)->step;

        return ! $traderOrder->checkOrderStepComplete($previousStep);
    }

    protected function isClientWakalaStepCompleted(TraderOrder $traderOrder): bool
    {
        $clientWakala = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::ClientWakala,
            'bursam' => BursamMurabhaStep::ClientWakala,
        };

        return $traderOrder->checkOrderStepComplete($clientWakala);
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
        $contractSigned = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::ContractSigned,
            'bursam' => BursamMurabhaStep::ContractSigned,
        };

        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
                ->getPreviousStepOf($contractSigned)->step
        );
    }

    protected function isContractSignedStepCompleted(TraderOrder $traderOrder): bool
    {
        $contractSigned = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::ContractSigned,
            'bursam' => BursamMurabhaStep::ContractSigned,
        };

        return $traderOrder->checkOrderStepComplete($contractSigned);
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
     */
    protected function handleProceedContractAndClientWakala(TraderOrder $traderOrder): array
    {
        $lastHistory = $traderOrder->traderHistories()->latest('id')->first();

        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        if (
            $order->is_verification_required || is_null($lastHistory)
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        ProcessProceedContractAndClientWakala::dispatchSync($traderOrder->id);

        return [];
    }
}
