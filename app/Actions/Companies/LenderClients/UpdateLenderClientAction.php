<?php

namespace App\Actions\Companies\LenderClients;

use App\Actions\Contracts\Companies\LenderClients\UpdateLenderClient;
use App\Models\ClientAutoSellPeriod;
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

        // Create new periods
        $newPeriods = $periodsCollection->filter(fn ($p) => ! isset($p['id']))->values();
        if ($newPeriods->isNotEmpty()) {
            $companyLenderClient->autoSellPeriods()->createMany($newPeriods);
        }

        // Delete periods
        $deletedPeriods = $periodsCollection->filter(fn ($p) => isset($p['is_deleted']) && $p['is_deleted'])->pluck('id');
        ClientAutoSellPeriod::whereIn('id', $deletedPeriods)->delete();

        // Update periods
        $editPeriods = $periodsCollection->filter(fn ($p) => isset($p['id']))->keyBy('id');
        foreach ($editPeriods as $period) {
            ClientAutoSellPeriod::where('id', $period['id'])
                ->where(fn ($query) => $query->where('effective_start', '!=', $period['effective_start'])
                    ->orWhere('effective_end', '!=', $period['effective_end']))
                ->update(
                    Arr::only($period, ['effective_start', 'effective_end'])
                );
        }
    }
}
