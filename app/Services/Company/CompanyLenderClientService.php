<?php

namespace App\Services\Company;

use App\Models\CompanyLenderClient;
use App\Models\FinancingOrder;
use Illuminate\Support\Carbon;

class CompanyLenderClientService
{
    public static function findActiveAutoCompletePeriodId(FinancingOrder $financingOrder): ?int
    {
        $client = CompanyLenderClient::with('autoSellPeriods')->where([
            'national_id' => $financingOrder->national_id,
            'company_id' => $financingOrder->company_id,
        ])->first();

        if (! $client || ! $client->auto_complete_sell) {
            return null;
        }

        $createdAt = Carbon::parse($financingOrder->created_at);

        foreach ($client->autoSellPeriods as $period) {
            $start = Carbon::parse($period['effective_start'])->startOfDay();
            $end = Carbon::parse($period['effective_end'])->endOfDay();

            if ($createdAt->between($start, $end)) {
                return $period['id'];
            }
        }

        return null;
    }
}
