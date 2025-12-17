<?php

namespace App\Actions\Companies\LenderClients;

use App\Actions\Contracts\Companies\LenderClients\CreateLenderClient;
use App\Models\CompanyLenderClient;
use App\Models\Lender;
use Illuminate\Support\Arr;

class CreateLenderClientAction implements CreateLenderClient
{
    public function handle(Lender $lender, array $data): CompanyLenderClient
    {
        // Normalize and prepare data
        $autoCompleteSell = filter_var($data['auto_complete_sell'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $data['company_id'] = $lender->id;
        $data['auto_complete_sell'] = $autoCompleteSell;

        // Create the lender client
        $client = CompanyLenderClient::create(
            Arr::only($data, [
                'name',
                'type',
                'national_id',
                'company_id',
                'auto_complete_sell',
            ])
        );

        // Store auto sell periods if needed
        if ($autoCompleteSell) {
            $client->autoSellPeriods()->createMany($data['auto_sell_periods']);
        }

        return $client;
    }
}
