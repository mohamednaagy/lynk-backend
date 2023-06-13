<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TransactionReason extends Enum
{
    const OrderCreationFee = 1;

    const DepositByEdaat = 2;

    const ManualDeposit = 3;

    const VatPercentageFee = 4;

    const RefundAfterCancellation = 5;

    public static array $reasonsAssociatedWithZatcaInvoice = [
        TransactionReason::OrderCreationFee,
        TransactionReason::VatPercentageFee,
    ];
}
