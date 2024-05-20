<?php

namespace App\Models;

class Supplier extends Company
{
    public function locations()
    {
        return $this->hasMany(SupplierLocation::class, 'company_id');
    }

    public function commodityItems()
    {
        return $this->hasMany(CommodityItem::class, 'company_id');
    }

    public function detail()
    {
        return $this->hasOne(CompanySupplierDetail::class, 'company_id');
    }
}
