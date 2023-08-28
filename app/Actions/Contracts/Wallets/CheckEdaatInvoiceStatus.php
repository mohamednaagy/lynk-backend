<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\EdaatInvoice;

interface CheckEdaatInvoiceStatus
{
    public function handle(EdaatInvoice $edaatInvoice): void;
}
