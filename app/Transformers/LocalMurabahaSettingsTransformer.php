<?php

namespace App\Transformers;

use App\Support\LocalMurabahaSettings\LocalMurabaha;
use League\Fractal\TransformerAbstract;

class LocalMurabahaSettingsTransformer extends TransformerAbstract
{
    public function transform(LocalMurabaha $settings): array
    {
        return [
            'default_trade_order_rotation_count' => $settings->getDefaultTradeOrderRoatationCount(),
            'default_contract_sign_time_limit' => $settings->getDefaultContractSignTimeLimit(),
            'default_customer_delivery_confirmation_time_limit' => $settings->getDefaultCustomerDeliveryConfirmationTimeLimit(),
        ];
    }
}
