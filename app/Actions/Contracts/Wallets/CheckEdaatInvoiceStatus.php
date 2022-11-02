<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;

interface CheckEdaatInvoiceStatus
{
    public function __construct(
        EdaatService $edaatService,
        CreateTransactions $createTransactions
    );

    public function handle(EdaatInvoice $edaatInvoice): void;
}
