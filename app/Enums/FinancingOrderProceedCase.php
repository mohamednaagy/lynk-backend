<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderProceedCase extends Enum implements LocalizedEnum
{
    // TODO_LYNKMRBHA-1062-BE-proceed-order
    const ALLOWED_TO_PROCEED_STATUS = [
        'BURSAM' => [
            'wakala',
            'contract_signed',
        ],
        'lynk' => [
            'complete',
        ],
        'naser' => [
            'complete',
        ],
    ];

    const ClientWakalaAccepted = 'CLIENT_WAKALA_ACCEPTED';

    const ContractSigned = 'CONTRACT_SIGNED';

    const ContractAndClientWakalaCompleted = 'CONTRACT_AND_CLIENT_WAKALA_COMPLETED';
}
