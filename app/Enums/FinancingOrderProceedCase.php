<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderProceedCase extends Enum implements LocalizedEnum
{
    const ClientWakalaAccepted = 1;

    const ContractSigned = 2;

    const ContractAndClientWakalaCompleted = 3;

    const ContractSignedDelivery = 4;

    const IgnoreAndSell = 5;

    const ConfirmDeliver = 6;

    /**
     * Mapping from integer constants to string descriptions
     *
     * @var array<int, string>
     */
    const VALUE_TO_DESCRIPTION = [
        self::ClientWakalaAccepted => 'CLIENT_WAKALA_ACCEPTED',
        self::ContractSigned => 'CONTRACT_SIGNED',
        self::ContractAndClientWakalaCompleted => 'CONTRACT_AND_CLIENT_WAKALA_COMPLETED',
        self::ContractSignedDelivery => 'CONTRACT_SIGNED_DELIVERY',
        self::IgnoreAndSell => 'IGNORE_AND_SELL',
        self::ConfirmDeliver => 'CONFIRM_DELIVER',
    ];

    /**
     * Mapping from string descriptions to integer constants
     *
     * @var array<string, int>
     */
    const DESCRIPTION_TO_VALUE = [
        'CLIENT_WAKALA_ACCEPTED' => self::ClientWakalaAccepted,
        'CONTRACT_SIGNED' => self::ContractSigned,
        'CONTRACT_AND_CLIENT_WAKALA_COMPLETED' => self::ContractAndClientWakalaCompleted,
        'CONTRACT_SIGNED_DELIVERY' => self::ContractSignedDelivery,
        'IGNORE_AND_SELL' => self::IgnoreAndSell,
        'CONFIRM_DELIVER' => self::ConfirmDeliver,
    ];

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
            ],
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
            ],
        ],
        Trader::Dmcc => [
            'v1' => [
                self::ClientWakalaAccepted,
                self::ContractSigned,
                self::ContractAndClientWakalaCompleted,
            ],
        ],
        Trader::FakeDmcc => [
            'v1' => [
                self::ClientWakalaAccepted,
                self::ContractSigned,
                self::ContractAndClientWakalaCompleted,
            ],
        ],
    ];

    public static function getDescription($value): string
    {
        return self::VALUE_TO_DESCRIPTION[$value] ?? self::getKey($value);
    }

    public static function getKeyByDescription(string $description): ?int
    {
        return self::DESCRIPTION_TO_VALUE[$description] ?? null;
    }
}
