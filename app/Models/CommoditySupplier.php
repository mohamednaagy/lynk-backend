<?php

namespace App\Models;

use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CommoditySupplier extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'legal_name',
        'unique_name',
        'description',
        'status',
        'market_type',
    ];

    protected $casts = [
        'status' => CommoitySupplierStatus::class,
        'market_type' => CommoitySupplierMarketType::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status']);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'supplier_user');
    }
}
