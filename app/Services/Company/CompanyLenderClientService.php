<?php

namespace App\Services\Company;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use App\Models\Lender;
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

    /**
     * @throws \Exception
     */
    public static function getLenderCurrentBalance(Lender $lender): float
    {
        $balances = app(GetLenderBalance::class)->handle($lender);
        /* @var \Cknow\Money\Money $balance */
        $balance = $balances['balance'];

        return (float) $balance->convertAndFormatByDecimal();
    }
}
