<?php

namespace App\Models;

use Illuminate\Support\Facades\Route;

class Lender extends Company
{
    public function getMorphClass()
    {
        return Company::class;
    }

    public function lenderDetail()
    {
        return $this->hasOne(CompanyLenderDetail::class, 'company_id', 'id');
    }

    public function getWallet(string $name, bool $lock = true): ?Wallet
    {
       return parent::getWallet($name, $lock);
    }

}
