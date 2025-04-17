<?php

namespace App\Actions\Companies\LenderClients;

use App\Actions\Contracts\Companies\LenderClients\UpdateLenderClient;
use App\Models\CompanyLenderClient;
use Illuminate\Support\Arr;

class UpdateLenderClientAction implements UpdateLenderClient
{
    public function handle(CompanyLenderClient $companyLenderClient, array $data): CompanyLenderClient
    {
        $companyLenderClient->update(
            Arr::only($data, [
                'name',
                'national_id',
                'auto_complete_sell',
            ])
        );

        if ($data['auto_complete_sell']) {
            $this->syncAutoSellPeriods($companyLenderClient, $data['auto_sell_periods']);
        }

        return $companyLenderClient;
    }

    protected function syncAutoSellPeriods(CompanyLenderClient $companyLenderClient, array $periods): void
    {
        $periodsCollection = collect($periods);

        $editPeriods = $periodsCollection->filter(fn ($p) => isset($p['id']))->keyBy('id');
        $newPeriods = $periodsCollection->filter(fn ($p) => ! isset($p['id']))->values();

        $existingPeriods = $companyLenderClient->autoSellPeriods;

        foreach ($existingPeriods as $existingPeriod) {
            $editPeriod = $editPeriods[$existingPeriod->id] ?? null;
            if ($editPeriod) {
                if (
                    $existingPeriod->effective_start !== $editPeriod['effective_start'] ||
                    $existingPeriod->effective_end !== $editPeriod['effective_end']
                ) {
                    $existingPeriod->update(
                        Arr::only($editPeriod, ['effective_start', 'effective_end'])
                    );
                }
            }
        }

        if ($newPeriods->isNotEmpty()) {
            $companyLenderClient->autoSellPeriods()->createMany($newPeriods);
        }
    }
}
