<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LocalMarketUnitRotation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'inventory_unit_id',
        'is_need_rotations_update',
        'number_of_rotations',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function inventoryUnit()
    {
        return $this->belongsTo(LocalMarketInventoryUnits::class, 'inventory_unit_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
