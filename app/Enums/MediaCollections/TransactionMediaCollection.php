<?php

namespace App\Enums\MediaCollections;

use BenSampo\Enum\Enum;

final class TransactionMediaCollection extends Enum
{
    public const Attachments = 'transaction_attachments';

    public const VoucherInvoice = 'voucher_invoice';
}
