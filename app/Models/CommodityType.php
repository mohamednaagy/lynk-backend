<?php

namespace App\Models;

use App\Enums\CommodityTypeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CommodityType extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = [
        'name',
        'unique_name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => CommodityTypeStatus::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status']);
    }

    public function scopeActive(Builder $query, ?int $value): Builder
    {
        return match ($value) {
            1 => $query->where('status', CommodityTypeStatus::Active()),
            2 => $query->where('status', CommodityTypeStatus::Inactive()),
            default => $query,
        };
    }
}
