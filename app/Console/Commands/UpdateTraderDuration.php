<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Trader;
use App\Models\TraderOrder;
use App\Services\TraderOrder\StepDurationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class UpdateTraderDuration extends Command
{
    protected $signature = 'trader-orders:update-duration
                            {--chunk-size=1000 : Number of records to process at once}';

    protected $description = 'Update the duration for all existing trader orders';

    public function __construct(
        private readonly StepDurationService $stepDurationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunkSize = (int) ($this->option('chunk-size') ?? 1000);

        $stepsConfig = $this->getStepsConfig();

        $query = TraderOrder::query()
            ->whereIn('provider', [Trader::Bursam, Trader::Lynk])
            ->select(['id', 'provider', 'version', 'contract_signed_type', 'financing_order_id'])
            ->with([
                'traderHistories' => fn ($q) => $q->select(['id', 'trader_order_id', 'action', 'created_at']),
            ]);

        $totalTraderOrders = $query->count();
        if ($totalTraderOrders === 0) {
            $this->info('No trader orders need to be updated.');

            return 0;
        }

        $this->info("Updating {$totalTraderOrders} trader orders...");

        $bar = $this->output->createProgressBar($totalTraderOrders);
        $bar->start();

        $query->chunkById($chunkSize, function ($traderOrders) use ($stepsConfig, $bar): void {
            foreach ($traderOrders as $traderOrder) {
                $this->updateDurationsForTraderOrder($traderOrder, $stepsConfig);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated {$totalTraderOrders} trader orders.");

        $dmccQuery = TraderOrder::query()
            ->whereIn('provider', [Trader::FakeDmcc, Trader::Dmcc])
            ->select(['id', 'provider', 'version', 'contract_signed_type', 'financing_order_id'])
            ->with(['traderOrderDuration']);

        $totalDmccTraderOrders = $dmccQuery->count();
        if ($totalDmccTraderOrders > 0) {
            $this->info("Resetting duration for {$totalDmccTraderOrders} DMCC/FakeDMCC trader orders...");
            $dmccQuery->chunkById($chunkSize, function ($traderOrders): void {
                foreach ($traderOrders as $traderOrder) {
                    $traderOrder->traderOrderDuration()->updateOrCreate(
                        ['trader_order_id' => $traderOrder->id],
                        [
                            'purchasing_commodity' => 0,
                            'contract_signed' => 0,
                            'commodity_sold_to_customer' => 0,
                            'client_wakala' => 0,
                            'murabha_offer_issued' => 0,
                            'murabaha_sale_completed' => 0,
                        ]);

                }
            });
            $this->info("Successfully reset duration for {$totalDmccTraderOrders} DMCC/FakeDMCC trader orders.");
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function getStepsConfig(): array
    {
        return [
            'lynk-v1' => Config::get('murabha-steps.lynk-step-duration.v1'),
            'lynk-v2' => Config::get('murabha-steps.lynk-step-duration.v2'),
            'bursam-v1' => Config::get('murabha-steps.bursam-step-duration.v1'),
            'bursam-v2' => Config::get('murabha-steps.bursam-step-duration.v2'),
            'dmcc-v1' => Config::get('murabha-steps.dmcc-step-duration.v1'),
            'dmcc-v2' => Config::get('murabha-steps.dmcc-step-duration.v2'),
            'fake-v1' => Config::get('murabha-steps.fake-step-duration.v1'),
            'fake-v2' => Config::get('murabha-steps.fake-step-duration.v2'),
        ];
    }

    /**
     * @param  array<string, mixed>  $stepsConfig
     */
    private function updateDurationsForTraderOrder(TraderOrder $traderOrder, array $stepsConfig): void
    {
        $provider = (string) $traderOrder->provider;
        $version = (string) $traderOrder->version;
        $contractType = $traderOrder->contract_signed_type?->value;

        $stepConfig = $stepsConfig["{$provider}-{$version}"][$contractType];
        if (! is_array($stepConfig)) {
            return;
        }
        foreach ($stepConfig as $endHistoryAction => $stepDef) {
            if (! is_array($stepDef) || empty($stepDef['end_history'])) {
                continue;
            }
            if (! $traderOrder->traderHistories->contains('action', $endHistoryAction)) {
                continue;
            }
            $this->stepDurationService->setStepDuration($traderOrder, (int) $endHistoryAction);
        }
    }
}
