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

    public function __construct(
        protected readonly TraderOrderProceedCaseService $proceedCaseService,
        protected readonly AcceptClientWakala $acceptClientWakala
    ) {}

    /**
     * Process the client wakala acceptance for a trader order.
     *
     * @param  TraderOrder  $traderOrder  The trader order to process
     * @param  UploadedFile|null  $signedClientWakala  Optional signed client wakala file
     * @param  bool  $forceToProceed  Whether to force proceeding despite validation
     *
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function handle(TraderOrder $traderOrder, ?UploadedFile $signedClientWakala = null, bool $forceToProceed = false): array
    {
        if (! $traderOrder->order) {
            Log::channel('bursam')->warning('ProceedClientWakalaAccepted: Order not found for trader order', [
                'traderOrderId' => $traderOrder->id,
            ]);

            return [];
        }

        $proceedCaseHandler = TraderOrderProceedCaseFactory::handle(
            FinancingOrderProceedCase::ClientWakalaAccepted
        );

        if (! $proceedCaseHandler->canProceed($traderOrder, $forceToProceed)) {
            throw new OrderStatusDoesNotFollowSequenceException([
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
            ]);
        }

        $this->attachSignedClientWakalaIfProvided($traderOrder, $signedClientWakala);
        $this->proceedCaseService->createCase($traderOrder->id, FinancingOrderProceedCase::ClientWakalaAccepted);
        $this->acceptClientWakala->handle($traderOrder);

        return [];
    }

    /**
     * Attach the signed client wakala file to the trader order if provided.
     *
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    protected function attachSignedClientWakalaIfProvided(TraderOrder $traderOrder, ?UploadedFile $signedClientWakala): void
    {
        if ($signedClientWakala === null) {
            return;
        }

        $traderOrder->addMedia($signedClientWakala)
            ->toMediaCollection(TraderOrderMediaCollection::SignedClientWakala);
    }
}
