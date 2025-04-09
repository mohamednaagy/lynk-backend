<?php

namespace App\Actions\Companies\LenderClients;

use App\Actions\Contracts\Companies\LenderClients\CreateLenderClient;
use App\Models\Company;
use App\Models\CompanyLenderClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateLenderClientAction implements CreateLenderClient
{
    public function handle(Company $lender, array $data): CompanyLenderClient
    {
        // Cast string to boolean
        $autoCompleteSell = filter_var($data['auto_complete_sell'], FILTER_VALIDATE_BOOLEAN);

        $data['company_id'] = $lender->id;
        $data['auto_complete_sell'] = $autoCompleteSell;

        $client = CompanyLenderClient::create(
            Arr::only(
                $data,
                [
                    'name',
                    'type',
                    'national_id',
                    'company_id',
                    'auto_complete_sell',
                ]
            )
        );

        if ($autoCompleteSell) {
            $autoSellPeriods = $this->prepareAutoSellPeriods($data['auto_sell_periods'], $client->id);

            // Perform the bulk insert
            DB::table('client_auto_sell_periods')->insert($autoSellPeriods);
        }

        return $client;
    }

    private function prepareAutoSellPeriods(array $autoSellPeriods, $clientId): array
    {
        $currentTimestamp = now();

        return array_map(function ($record) use ($clientId, $currentTimestamp) {
            $result = Arr::only($record, [
                'effective_start',
                'effective_end',
            ]);

            $result['company_lender_client_id'] = $clientId;
            $result['created_at'] = $currentTimestamp;
            $result['updated_at'] = $currentTimestamp;

            return $result;
        }, $autoSellPeriods);
    }
}
