<?php

namespace App\Models;


class Lender extends Company
{
    public function lenderDetail()
    {
        return $this->hasOne(CompanyLenderDetail::class, 'company_id');
    }
}
