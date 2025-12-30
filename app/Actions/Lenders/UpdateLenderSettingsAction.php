<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\UpdateLenderSettings;
use App\Models\Lender;
use Illuminate\Support\Arr;

class UpdateLenderSettingsAction implements UpdateLenderSettings
{
    public function handle(Lender $lender, array $data): void
    {
        $lenderDetail = $lender->lenderDetail;

        // Update model attributes without saving yet
        $lenderDetail->fill(Arr::only($data, [
            'require_initiate_trade_request',
            'does_order_require_approval',
            'force_unique_reference_number',
            'token_expire_in',
        ]));

        // If token_expire_in has changed, bump the version
        if ($lenderDetail->isDirty('token_expire_in')) {
            $lenderDetail->token_version += 1;
        }

        $lenderDetail->save();
    }
}
