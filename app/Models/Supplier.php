<?php

namespace App\Models;

class Supplier extends Company
{
    public function locations()
    {
        return $this->hasMany(SupplierLocation::class, 'company_id');
    }
}
