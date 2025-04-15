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
            $this->updateAutoSellPeriods($companyLenderClient, $data['auto_sell_periods']);
        }

        return $companyLenderClient;
    }

    protected function updateAutoSellPeriods(CompanyLenderClient $companyLenderClient, array $periods): void
    {
        foreach ($periods as $period) {
            $existingPeriod = $companyLenderClient->autoSellPeriods()->where('id', $period['id'])->first();
            if ($existingPeriod->effective_start !== $period['effective_start'] || $existingPeriod->effective_end !== $period['effective_end']) {
                $existingPeriod->update(
                    Arr::only($period, [
                        'effective_start',
                        'effective_end',
                    ])
                );
            }
        }
    }
}
