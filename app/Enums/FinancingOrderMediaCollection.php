<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FinancingOrderMediaCollection extends Enum
{
    const Contract = 'contract';

    const ClientWakala = 'client_wakala';

    const BankWakala = 'bank_wakala';

    const PowerOfAttorney = 'power_of_attorney';

    const PromiseToPurchase = 'promise_to_purchase';

    const MurabahaPurchaseOrder = 'murabaha_purchase_order';

    const TransferOwnershipToLender = 'transfer_ownership_to_lender';

    const SellingCommodityToCustomer = 'selling_commodity_to_customer';

    const WarrantAmendmentExceptWarrantNo = 'warrant_amendment_except_warrant_no';
}
