<?php

namespace App\Models;

use App\Enums\FinancingOrderStatus;
use App\Support\QueryScoper\HasScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class FinancingOrder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, BelongsToTenant, LogsActivity, HasScopes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_number',
        'national_id',
        'phone_number',
        'amount',
        'selling_price',
        'status',
        'approved_at',
        'approver_id',
        'creator_id',
        'creator_type',
        'reason',
    ];

    protected $casts = [
        'status' => FinancingOrderStatus::class,
        'approved_at' => 'datetime',
        'phone_number' => E164PhoneNumberCast::class,
    ];

    protected function phoneNumberCountryCode(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => "{$this->phone_number->getCountry()}",
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
        // Chain fluent methods for configuration options
    }

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
}
