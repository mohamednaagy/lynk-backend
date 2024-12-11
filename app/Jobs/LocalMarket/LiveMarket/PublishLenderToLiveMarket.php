<?php

namespace App\Jobs\LocalMarket\LiveMarket;

use App\Models\Company;
use App\Services\LocalMarket\LiveMarketService;

class PublishLenderToLiveMarket extends BaseLiveMarketJob
{
    protected Company $lender;

    public function __construct(Company $lender)
    {
        parent::__construct();
        $this->lender = $lender;
    }

    public function handle(LiveMarketService $liveMarketService): void
    {
        $this->logJobStart('Publishing lender to live market', [
            'lender_id' => $this->lender->id,
            'lender_name' => $this->lender->name,
            'status' => $this->lender->status->value,
        ]);

        try {
            $liveMarketService->handleNewCompany($this->lender);

            $this->logJobSuccess('Successfully published lender to live market', [
                'lender_id' => $this->lender->id,
                'lender_name' => $this->lender->name,
            ]);
        } catch (\Throwable $e) {
            $this->logJobError('Failed to publish lender to live market', $e);
            throw $e;
        }
    }

    protected function getFailedJobContext(): array
    {
        return [
            'lender_id' => $this->lender->id,
            'lender_name' => $this->lender->name,
            'status' => $this->lender->status->value,
            'type' => $this->lender->type->value,
            'created_at' => $this->lender->created_at,
        ];
    }
}
