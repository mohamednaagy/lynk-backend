<?php

namespace App\Enums;

use App\Enums\Contracts\Murabha\TraderMurabhaStepInterface;
use BenSampo\Enum\Enum;

final class DmccMurabhaStep extends Enum implements TraderMurabhaStepInterface
{
    const TraderOrderCreated = 'trader_order_created';

    const PurchasingCommodity = 'purchasing_commodity';

    const ContractSigned = 'contract_signed';

    const CommoditySoldToCustomer = 'commodity_sold_to_customer';

    const ClientWakala = 'client_wakala';

    const MurabhaOfferIssued = 'murabha_offer_issued';

    const MurabahaSaleCompleted = 'murabaha_sale_completed';

    public static function getStepsOfVersion(?string $version = null): array
    {
        $version = $version ?? get_latest_version_of_trader('dmcc');

        return match ($version) {
            'v1' => get_murabha_steps('dmcc', 'v1'),
            'v2' => get_murabha_steps('dmcc', 'v2'),
            default => throw new \InvalidArgumentException('Invalid version')
        };
    }
}
