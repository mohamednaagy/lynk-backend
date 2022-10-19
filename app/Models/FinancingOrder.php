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
    ];

    protected $casts = [
        'status' => FinancingOrderStatus::class,
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

    public function getPowerOfAttorneyAttribute()
    {
        return $this->getFirstMediaUrl('power_of_attorney');
    }

    public function getContractAttribute()
    {
        return $this->getFirstMediaUrl('contract');
    }
}
