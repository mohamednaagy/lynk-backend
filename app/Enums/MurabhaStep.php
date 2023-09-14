<?php

namespace App\Enums;

use App\Enums\Contracts\Murabha\TraderMurabhaStepInterface;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class MurabhaStep extends Enum implements TraderMurabhaStepInterface, LocalizedEnum
{
    const TraderOrderCreated = 'trader_order_created';

    const PurchasingCommodity = 'purchasing_commodity';

    const ContractSigned = 'contract_signed';

    const CommoditySoldToCustomer = 'commodity_sold_to_customer';

    const TransferOwnershipToLender = 'transfer_ownership_to_lender';

    const ClientWakala = 'client_wakala';

    const MurabhaOfferIssued = 'murabha_offer_issued';

    const MurabahaSaleCompleted = 'murabaha_sale_completed';

    public static function getStepsOfVersion(string $driver = null, string $version = null): array
    {
        $driver ??= config('trader.default');
        $version ??= get_latest_version_of_trader($driver);

        return get_murabha_steps($driver, $version);
    }
}
