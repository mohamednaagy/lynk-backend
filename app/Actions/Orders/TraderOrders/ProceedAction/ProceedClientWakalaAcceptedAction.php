<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedClientWakalaAccepted;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class ProceedClientWakalaAcceptedAction implements ProceedClientWakalaAccepted
{
    use TraderHelperTrait;

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws \Exception
     */
    public function handle(TraderOrder $traderOrder, ?UploadedFile $signedClientWakala = null, bool $forceToProceed = false): array
    {
        $order = $traderOrder->order;

        if(! $order){
            Log::channel('bursam')->info('ProceedClientWakalaAccepted: traderOrderId: '.$traderOrder->id.' - Order is null', [
                'traderOrderId' => $traderOrder->id,
                'order' => $order?->id,
            ]);
            return [];
        }

        Log::channel('bursam')->info('ProceedClientWakalaAccepted: traderOrderId: '.$traderOrder->id.' - data', [
            'traderOrderId' => $traderOrder->id,
            'order' => $order?->id,
            'forceToProceed' => $forceToProceed,
            'is_verification_required' => $order->is_verification_required,
            'isClientWakalaStepCompleted' => $this->isClientWakalaStepCompleted($traderOrder),
            'isPreviousStepOfClientWakalaNotCompleted' => $this->isPreviousStepOfClientWakalaNotCompleted($traderOrder),
        ]);
        
        if (
            $this->isPreviousStepOfClientWakalaNotCompleted($traderOrder)
            || ($forceToProceed === false && $order->is_verification_required)
            || ($forceToProceed === false && $this->isClientWakalaStepCompleted($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        if ($signedClientWakala) {
            $traderOrder->addMedia($signedClientWakala)
                ->toMediaCollection(TraderOrderMediaCollection::SignedClientWakala);
        }
        app(TraderOrderProceedCaseService::class)->createCase($traderOrder->id, FinancingOrderProceedCase::ClientWakalaAccepted);

        app(AcceptClientWakala::class)->handle($traderOrder);

        return [];
    }

    protected function isPreviousStepOfClientWakalaNotCompleted(TraderOrder $traderOrder): bool
    {
        $previousStep = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type))
            ->getPreviousStepOf(MurabhaStep::ClientWakala)->step;

        return ! $traderOrder->checkOrderStepComplete($previousStep);
    }

    protected function isClientWakalaStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakala);
    }
}
