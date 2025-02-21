<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Enums\LocalMarketOrderStatus;
use App\Jobs\LocalMarket\SellConfirmation\Enums\SellConfirmationStatus;
use App\Jobs\LocalMarket\SellConfirmation\Exceptions\SellConfirmationGenerationException;
use App\Models\LocalMarketOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Lynk\Strategies\LynkV1Driver;
use Exception;
use Stancl\Tenancy\Database\TenantScope;

class GenerateSellConfirmationCertificate extends BaseSellConfirmation
{
    public function __construct(private ?int $localMarketOrderId = null)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        // Retrieve all orders with a status of 'ready for certificate'
        LocalMarketOrder::query()
            ->whereIn('status', [LocalMarketOrderStatus::CommoditiesSell, LocalMarketOrderStatus::Completed])
            ->where('sell_confirmation_status', SellConfirmationStatus::ReadyForCertificate)
            ->when($this->localMarketOrderId, fn ($q) => $q->where('id', $this->localMarketOrderId))
            ->chunkById(self::CHUNK_SIZE, function ($orders) {
                try {
                    foreach ($orders as $order) {
                        $this->generateCertificate($order);
                        $this->updateCertificateStatus($order->id, SellConfirmationStatus::Generated);
                    }
                } catch (Exception $e) {
                    self::logError('GenerateSellConfirmationCertificate failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $this->updateCertificateStatus($order->id, SellConfirmationStatus::Error);

                    throw new SellConfirmationGenerationException($e->getMessage());
                }

            });
    }

    /**
     * Unique identifier for job deduplication.
     */
    public function uniqueId(): string
    {
        return $this->localMarketOrderId
            ? __CLASS__.'_'.$this->localMarketOrderId
            : parent::uniqueId();
    }

    private function generateCertificate(LocalMarketOrder $order): void
    {
        $traderOrder = TraderOrder::with([
            //retrieve the financing order without checking the tenant.
            'order' => fn ($q) => $q->withoutGlobalScope(TenantScope::class),
        ])->where('reference', $order->external_order_no)->firstOrFail();

        $lynkV1Driver = app(LynkV1Driver::class);
        $lynkV1Driver->createSellConfirmationDocument($traderOrder);
    }

    private function updateCertificateStatus(int $orderId, int $status): void
    {
        LocalMarketOrder::withoutEvents(fn () => LocalMarketOrder::whereId($orderId)->update([
            'sell_confirmation_status' => $status,
        ])
        );
    }
}
