<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class BursamProductCode extends Enum implements LocalizedEnum
{
    const PlasticResinB = 'PR-B MSIA14';

    const PlasticResinBDev = 'PR-B-MSIA14';

    const PlasticResinA = 'PR-A MSIA14';

    const PlasticResinADev = 'PR -A-MY-14';

    const CrudePalmOil = 'CPO-MSIA-09';

    const PlumbumLead = 'PB-LEAD -19';

    const PlumbumLeadDev = 'PB-LEAD- 19';

    const RbdPalmOlein = 'OLN-MSIA-12';

    const TimberHardwood = 'TBH-MSIA-12';

    const TimberSoftwood = 'TBS-MSIA-12';

    public static function getProductCodes(string $env): array
    {
        return match ($env) {
            default => [
                self::CrudePalmOil,
                self::PlasticResinBDev,
                self::RbdPalmOlein,
                self::PlumbumLeadDev,
                self::PlasticResinADev,
            ],
            'production' => [
                self::PlasticResinB,
                self::PlasticResinA,
                self::CrudePalmOil,
                self::PlumbumLead,
                self::RbdPalmOlein,
                self::TimberHardwood,
                self::TimberHardwood,
            ],
        };
    }
}
