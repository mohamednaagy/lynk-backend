<?php

namespace App\Models;

use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompanySupplierDetail extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'description',
        'market_type',
        'status',
        'company_id',
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

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
