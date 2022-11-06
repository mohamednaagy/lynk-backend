<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class MediaCollection extends Enum
{
    const Contract = 'Contract';

    const ClientWakala = 'ClientWakala';

    const BankWakala = 'BankWakala';

    const PowerOfAttorney = 'PowerOfAttorney';

    const PromiseToPurchase = 'PromiseToPurchase';

    const MurabahaPurchaseOrder = 'MurabahaPurchaseOrder';

    const TransferOwnershipToLender = 'TransferOwnershipToLender';

    const SellingCommodityToCustomer = 'SellingCommodityToCustomer';

    const WarrantAmendmentExceptWarrantNo = 'WarrantAmendmentExceptWarrantNo';
}
