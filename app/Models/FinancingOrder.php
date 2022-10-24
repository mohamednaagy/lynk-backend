<?php

namespace App\Models;

use App\Enums\FinancingOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class FinancingOrder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_number',
        'national_id',
        'amount',
        'selling_price',
        'status',
        'approved_at',
        'approver_id',
        'creator_id',
        'creator_type',
    ];

    protected $casts = [
        'status' => FinancingOrderStatus::class,
        'approved_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('contract')
            ->singleFile();

        $this
            ->addMediaCollection('power_of_attorney')
            ->singleFile();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->morphTo('creator');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id', 'id');
    }

    public function getPowerOfAttorneyAttribute()
    {
        return $this->getFirstMediaUrl('power_of_attorney');
    }

    public function getContractAttribute()
    {
        return $this->getFirstMediaUrl('contract');
    }

    public function traderOrder()
    {
        return $this->hasMany(TraderOrder::class, 'order_id', 'id');
    }

    public function traderHistory()
    {
        return $this->hasMany(TraderHistory::class, 'order_id', 'id');
    }
}
