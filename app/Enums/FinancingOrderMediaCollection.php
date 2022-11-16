<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

class FinancingOrderMediaCollection extends Enum
{
    public const Contract = 'contract';

    public const ClientWakala = 'client_wakala';

    public const BankWakala = 'bank_wakala';

    public const PowerOfAttorney = 'power_of_attorney';

    public const PromiseToPurchase = 'promise_to_purchase';

    public const MurabahaPurchaseOrder = 'murabaha_purchase_order';

    public const TransferOwnershipToLender = 'transfer_ownership_to_lender';

    public const SellingCommodityToCustomer = 'selling_commodity_to_customer';

    public const WarrantAmendmentExceptWarrantNo = 'warrant_amendment_except_warrant_no';
}
