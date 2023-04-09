<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class BursaMurabhaStep extends Enum
{
    const TraderOrderCreated = 'trader_order_created';

    const PurchasingCommodity = 'purchasing_commodity';

    const ContractSigned = 'contract_signed';

    const CommoditySoldToCustomer = 'commodity_sold_to_customer';

    const ClientWakala = 'client_wakala';

    const MurabahaSaleCompleted = 'murabaha_sale_completed';

    public static function getStepsOfVersion($version)
    {
        $version = $version ?? config('trader.providers.bursa.latest');

        return match ($version) {
            'v1' => config('bursa-murabha-steps-versions.v1'),
            'v2' => config('bursa-murabha-steps-versions.v2'),
            default => throw new \InvalidArgumentException('Invalid version')
        };
    }
}
