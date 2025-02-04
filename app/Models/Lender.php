<?php

namespace App\Models;


class Lender extends Company
{
    public function getMorphClass()
    {
        return Company::class;
    }

    public function lenderDetail()
    {
        return $this->hasOne(CompanyLenderDetail::class, 'company_id');
    }

    public function getWallet(string $name, bool $lock = true): ?Wallet
    {
       return parent::getWallet($name, $lock);
    }
}
