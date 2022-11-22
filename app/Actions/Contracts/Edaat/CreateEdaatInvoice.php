<?php

namespace App\Actions\Contracts\Edaat;

use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;

interface CreateEdaatInvoice
{
    public function __construct(EdaatService $edaatService);

    public function handle($amount): EdaatInvoice;
}
