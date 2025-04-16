<?php

namespace App\Services\Company;

use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use Carbon\Carbon;

class CompanyLenderClientService
{
    public static function getAutoCompleteSellPeriod(CompanyLenderClient $client, Carbon $creationDate): ?ClientAutoSellPeriod
    {
        foreach ($client->autoSellPeriods as $period) {
            $start = $period->effective_start->startOfDay();
            $end = $period->effective_end->endOfDay();

            if ($creationDate->between($start, $end)) {
                Log::channel('bursam')->info('Auto complete sell period found', [
                    'client' => $client->id,
                    'period' => $period->id,
                ]);
                return $period;
            }
        }

        return null;
    }
}
