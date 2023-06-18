<?php

namespace App\Enums;

use App\Enums\Contracts\Murabha\TraderMurabhaStepInterface;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class BursamMurabhaStep extends Enum implements TraderMurabhaStepInterface, LocalizedEnum
{
    const TraderOrderCreated = 'trader_order_created';

    const PurchasingCommodity = 'purchasing_commodity';

    const ContractSigned = 'contract_signed';

    const CommoditySoldToCustomer = 'commodity_sold_to_customer';

    const TransferOwnershipToLender = 'transfer_ownership_to_lender';

    const ClientWakala = 'client_wakala';

    const MurabhaOfferIssued = 'murabha_offer_issued';

    const MurabahaSaleCompleted = 'murabaha_sale_completed';

    public static function getStepsOfVersion(?string $version = null): array
    {
        $version = $version ?? get_latest_version_of_trader('bursam');

        return match ($version) {
            'v1' => get_murabha_steps('bursam', 'v1'),
            'v2' => get_murabha_steps('bursam', 'v2'),
            default => throw new \InvalidArgumentException('Invalid version')
        };
    }

    public static function getDescriptionFromEnum(string $description): ?string
    {
        $mapping = [
            'trader_order_created' => self::TraderOrderCreated()->description,
            'purchasing_commodity' => self::PurchasingCommodity()->description,
            'contract_signed' => self::ContractSigned()->description,
            'commodity_sold_to_customer' => self::CommoditySoldToCustomer()->description,
            'transfer_ownership_to_lender' => self::TransferOwnershipToLender()->description,
            'client_wakala' => self::ClientWakala()->description,
            'murabha_offer_issued' => self::MurabhaOfferIssued()->description,
            'murabaha_sale_completed' => self::MurabahaSaleCompleted()->description,
        ];

        return $mapping[$description] ?? null;
    }
}
