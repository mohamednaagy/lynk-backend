<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Jobs\LocalMarket\SellConfirmation\Enums\SellConfirmationStatus;
use App\Jobs\LocalMarket\SellConfirmation\Exceptions\SellConfirmationGenerationException;
use App\Models\LocalMarketOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Lynk\Strategies\LynkV1Driver;
use Stancl\Tenancy\Database\TenantScope;

class GenerateSellConfirmationCertificate extends BaseSellConfirmation
{
    /**
     * Create a new job instance.
     */
    public function __construct(private ?int $localMarketOrderId = null)
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Step 1: If no specific order is given, get all orders with status "Ready for Certificate"
            $query = LocalMarketOrder::query()
                ->where('sell_confirmation_status', SellConfirmationStatus::ReadyForCertificate);

            if ($this->localMarketOrderId) {
                $query->where('id', $this->localMarketOrderId);
            }

            $query->chunkById(self::CHUNK_SIZE, function ($orders) {
                foreach ($orders as $order) {
                    // Step 3: Generate the Sell Confirmation Certificate
                    $this->generateCertificate($order);

                    // Step 4: Update status to "Generated"
                    LocalMarketOrder::withoutEvents(fn () => $order->update([
                        'sell_confirmation_status' => SellConfirmationStatus::Generated,
                    ]));
                }
            });

        } catch (\Exception $e) {
            self::logError('GenerateSellConfirmationCertificate failed', [
                'order_id' => $this->localMarketOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // If a specific order was targeted, update its status to "Error"
            if ($this->localMarketOrderId) {
                LocalMarketOrder::withoutEvents(fn () => LocalMarketOrder::where('id', $this->localMarketOrderId)
                    ->update(['sell_confirmation_status' => SellConfirmationStatus::Error]));
            }

            throw new SellConfirmationGenerationException($e->getMessage());
        }
    }

    /**
     * Unique identifier for job deduplication.
     */
    public function uniqueId(): string
    {
        return __CLASS__.'_'.($this->localMarketOrderId ?? 'all');
    }

    /**
     * Business logic for generating the Sell Confirmation Certificate.
     */
    private function generateCertificate(LocalMarketOrder $order)
    {
        $traderOrder = TraderOrder::with([
            'order' => fn ($q) => $q->withoutGlobalScope(TenantScope::class), //retrieve the financing order without checking the tenant.
        ])->where('reference', $order->external_order_no)->firstOrFail();

        $lynkV1Driver = app(LynkV1Driver::class);
        $lynkV1Driver->createSellConfirmationDocument($traderOrder);
    }
}
