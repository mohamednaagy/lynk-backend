<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderProceedCase extends Enum implements LocalizedEnum
{
    const ClientWakalaAccepted = 'CLIENT_WAKALA_ACCEPTED';

    const ContractSigned = 'CONTRACT_SIGNED';

    const ContractAndClientWakalaCompleted = 'CONTRACT_AND_CLIENT_WAKALA_COMPLETED';
}
