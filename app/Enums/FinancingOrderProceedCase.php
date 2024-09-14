<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderProceedCase extends Enum implements LocalizedEnum
{
    const ALLOWED_TO_PROCEED_STATUS = [
        Trader::Bursam => [
            self::ClientWakalaAccepted,
            self::ContractSigned,
            self::ContractAndClientWakalaCompleted,
        ],
        Trader::Lynk => [
            self::ContractAndClientWakalaCompleted,
            self::ContractSignedDelivery,
        ],
        Trader::Dmcc => [
            self::ClientWakalaAccepted,
            self::ContractSigned,
            self::ContractAndClientWakalaCompleted,
        ],
        Trader::FakeDmcc => [
            self::ClientWakalaAccepted,
            self::ContractSigned,
            self::ContractAndClientWakalaCompleted,
        ],
    ];

    const ClientWakalaAccepted = 'CLIENT_WAKALA_ACCEPTED';

    const ContractSigned = 'CONTRACT_SIGNED';

    const ContractAndClientWakalaCompleted = 'CONTRACT_AND_CLIENT_WAKALA_COMPLETED';

    const ContractSignedDelivery = 'CONTRACT_SIGNED_DELIVER';
}
