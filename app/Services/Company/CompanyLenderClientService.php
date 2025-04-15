<?php

namespace App\Services\Company;

use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use Carbon\Carbon;

class CompanyLenderClientService
{
    public static function getAutoCompleteSellPeriod(CompanyLenderClient $client, Carbon $creationDate): ?ClientAutoSellPeriod
    {
        if (! $client->auto_complete_sell) {
            return null;
        }

        foreach ($client->autoSellPeriods as $period) {
            $start = $period->effective_start->startOfDay();
            $end = $period->effective_end->endOfDay();

            if ($creationDate->between($start, $end)) {
                return $period;
            }
        }

        return null;
    }
}
