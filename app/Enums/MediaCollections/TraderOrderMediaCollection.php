<?php

namespace App\Enums\MediaCollections;

use BenSampo\Enum\Enum;

final class TraderOrderMediaCollection extends Enum
{
    const ClientWakala = 'client_wakala';

    const SignedClientWakala = 'signed_client_wakala';

    const LenderWakala = 'lender_wakala';

    const PromiseToPurchase = 'promise_to_purchase';

    const MurabahaPurchaseOrder = 'murabaha_purchase_order';

    const TransferOwnershipToLender = 'transfer_ownership_to_lender';

    const SellingCommodityToCustomer = 'selling_commodity_to_customer';

    const WarrantAmendmentExceptWarrantNo = 'warrant_amendment_except_warrant_no';

    const TtiHoldingCertificate = 'tti_holding_certificate';
}
