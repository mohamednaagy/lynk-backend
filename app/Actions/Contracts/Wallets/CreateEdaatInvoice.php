<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;
use Bavix\Wallet\Models\Wallet;

interface CreateEdaatInvoice
{
    public function __construct(EdaatService $edaatService);

    public function handle(Wallet $wallet, $amount): EdaatInvoice;
}
