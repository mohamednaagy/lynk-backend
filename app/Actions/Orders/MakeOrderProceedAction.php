<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedClientWakalaAccepted;
use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractAndClientWakalaCompleted;
use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractSigned;
use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractSignedDelivery;
use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedDeliveryConfirmation;
use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedIgnoreAndSell;
use App\Enums\FinancingOrderProceedCase;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class MakeOrderProceedAction implements MakeOrderProceed
{
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
        $signedClientWakala = $this->signedClientWakala;

        return match ($case) {
            FinancingOrderProceedCase::ClientWakalaAccepted => app(ProceedClientWakalaAccepted::class)->handle($traderOrder, $signedClientWakala, $forceToProceed),
            FinancingOrderProceedCase::ContractSigned => app(ProceedContractSigned::class)->handle($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::ContractSignedDelivery => app(ProceedContractSignedDelivery::class)->handle($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::IgnoreAndSell => app(ProceedIgnoreAndSell::class)->handle($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::ConfirmDeliver => app(ProceedDeliveryConfirmation::class)->handle($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::ContractAndClientWakalaCompleted => app(ProceedContractAndClientWakalaCompleted::class)->handle($traderOrder, $forceToProceed),
            default => []
        };
    }

    public function setSignedClientWakala(UploadedFile $signedClientWakala): static
    {
        $this->signedClientWakala = $signedClientWakala;

        return $this;
    }
}
