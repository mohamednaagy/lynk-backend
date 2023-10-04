<?php

namespace App\Enums\MediaCollections;

use BenSampo\Enum\Enum;

final class TransactionMediaCollection extends Enum
{
    public const Attachments = 'transaction_attachments';

    public const VoucherReceipt = 'voucher_receipt';

    public const ZatcaInvoice = 'zatca_invoice';
}
