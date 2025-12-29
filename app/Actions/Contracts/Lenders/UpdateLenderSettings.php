<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Lender;

interface UpdateLenderSettings
{
    public function handle(Lender $lender, array $data): void;
}
