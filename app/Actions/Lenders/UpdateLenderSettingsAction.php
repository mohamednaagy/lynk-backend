<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\UpdateLenderSettings;
use App\Models\Company;
use Illuminate\Support\Arr;

class UpdateLenderSettingsAction implements UpdateLenderSettings
{
    public function handle(Company $company, array $data): void
    {
        $company->update($data);
        $this->updateLenderDetails($company, $data);
    }

    private function updateLenderDetails(Company $company, array $data): void
    {
        $lenderDetail = $company->lender->lenderDetail;
        $oldExpireIn = (int) ($lenderDetail->token_expire_in);
        $newExpireIn = (int) ($data['token_expire_in'] ?? $oldExpireIn);

        $lenderDetailData = Arr::only($data, [
            'require_initiate_trade_request',
            'does_order_require_approval',
            'force_unique_reference_number',
            'token_expire_in',
        ]);

        if ($newExpireIn !== $oldExpireIn) {
            $lenderDetailData['token_version'] = $lenderDetail->token_version + 1;
        }

        $lenderDetail->updateOrCreate(
            ['company_id' => $company->id],
            $lenderDetailData
        );
    }
}
