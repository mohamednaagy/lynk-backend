<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderProceedCase extends Enum implements LocalizedEnum
{
    const ALLOWED_TO_PROCEED_STATUS = [
        Trader::Bursam => [
            'v1' => [
                self::ClientWakalaAccepted,
                self::ContractSigned,
                self::ContractAndClientWakalaCompleted,
            ],
            'v2' => [
                self::ClientWakalaAccepted,
                self::ContractSigned,
                self::ContractAndClientWakalaCompleted,
            ]
        ],
        Trader::Lynk => [
            'v1' => [
                self::ContractAndClientWakalaCompleted,
                self::ContractSignedDelivery,
                self::IgnoreAndSell,
                self::ConfirmDeliver,
            ],
            'v2' => [
                self::ContractSigned,
                self::ClientWakalaAccepted,
                self::ConfirmDeliver,
                self::ContractAndClientWakalaCompleted,
            ]
        ],
        Trader::Dmcc => [
            'v1' => [
                self::ClientWakalaAccepted,
                self::ContractSigned,
                self::ContractAndClientWakalaCompleted,
            ]
        ],
        Trader::FakeDmcc => [
            'v1' => [
                self::ClientWakalaAccepted,
                self::ContractSigned,
                self::ContractAndClientWakalaCompleted,
            ]
        ],
    ];

    const ClientWakalaAccepted = 'CLIENT_WAKALA_ACCEPTED';

    const ContractSigned = 'CONTRACT_SIGNED';

    const ContractAndClientWakalaCompleted = 'CONTRACT_AND_CLIENT_WAKALA_COMPLETED';

    const ContractSignedDelivery = 'CONTRACT_SIGNED_DELIVERY';

    const IgnoreAndSell = 'IGNORE_AND_SELL';

    const ConfirmDeliver = 'CONFIRM_DELIVER';
}
