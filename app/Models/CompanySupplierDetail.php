<?php

namespace App\Models;

use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySupplierDetail extends Model
{
    use HasFactory;

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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'company_id');
    }

    public function scopeActive(Builder $query, ?int $value): Builder
    {
        return match ($value) {
            1 => $query->where('status', CommoitySupplierStatus::Active()),
            2 => $query->where('status', CommoitySupplierStatus::Inactive()),
            default => $query,
        };
    }
}
