<?php

namespace App\Enums;

use App\Enums\Contracts\Murabha\MurabhaStepsInterface;
use BenSampo\Enum\Enum;

final class BursaMurabhaStep extends Enum implements MurabhaStepsInterface
{
    const TraderOrderCreated = 'trader_order_created';

    const PurchasingCommodity = 'purchasing_commodity';

    const ContractSigned = 'contract_signed';

    const CommoditySoldToCustomer = 'commodity_sold_to_customer';

    const ClientWakala = 'client_wakala';

    const MurabahaSaleCompleted = 'murabaha_sale_completed';

    public static function getStepsOfVersion(?string $version = null)
    {
        $version = $version ?? get_latest_version_of_trader('bursa');

        return match ($version) {
            'v1' => get_murabha_steps('bursam', 'v1'),
            'v2' => get_murabha_steps('bursam', 'v2'),
            default => throw new \InvalidArgumentException('Invalid version')
        };
    }
}
