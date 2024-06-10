<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CommodityItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'unique_name',
        'description',
        'company_id',
        'commodity_type_id',
        'min_price',
        'max_price',
        'volume_sellable_unit',
        'currency_id',
        'measurement_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'company_id');
    }

    public function type()
    {
        return $this->belongsTo(CommodityType::class, 'commodity_type_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class);
    }

}
