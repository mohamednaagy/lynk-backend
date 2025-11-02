<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedClientWakalaAccepted;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Factories\TraderOrders\TraderOrderProceedCaseFactory;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
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

        if (! $order) {
            Log::channel('bursam')->info('ProceedClientWakalaAccepted: traderOrderId: '.$traderOrder->id.' - Order is null', [
                'traderOrderId' => $traderOrder->id,
                'order' => $order?->id,
            ]);

            return [];
        }

        $proceedCaseHandler = TraderOrderProceedCaseFactory::handle(FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ClientWakalaAccepted));
        $canProceed = $proceedCaseHandler->canProceed($traderOrder, $forceToProceed);
        if (! $canProceed) {
            throw new OrderStatusDoesNotFollowSequenceException(
                [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                ]
            );
        }

        if ($signedClientWakala) {
            $traderOrder->addMedia($signedClientWakala)
                ->toMediaCollection(TraderOrderMediaCollection::SignedClientWakala);
        }
        app(TraderOrderProceedCaseService::class)->createCase($traderOrder->id, FinancingOrderProceedCase::ClientWakalaAccepted);

        app(AcceptClientWakala::class)->handle($traderOrder);

        return [];
    }
}
