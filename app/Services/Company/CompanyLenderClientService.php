<?php

namespace App\Services\Company;

use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CompanyLenderClientService
{
    public static function getAutoCompleteSellPeriod(CompanyLenderClient $client, Carbon $creationDate): ?ClientAutoSellPeriod
    {
        foreach ($client->autoSellPeriods as $period) {
            $start = $period->effective_start->startOfDay();
            $end = $period->effective_end->endOfDay();

            if ($creationDate->between($start, $end)) {
                log::channel(LOG_CHANNEL_BURSAM)->info('Auto complete sell period found for client_id => '.$client->id.' and period id => '.$period->id, [
                    'client' => $client->id,
                    'period' => $period->id,
                ]);

                return $period;
            }
        }

        return null;
    }
}
