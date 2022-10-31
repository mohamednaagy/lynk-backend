<?php

namespace App\Models;

use App\Enums\FinancingOrderStatus;
use App\Support\QueryScoper\HasScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class FinancingOrder extends Model implements HasMedia, Otpifiable
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
        'amount',
        'selling_price',
        'status',
        'approved_at',
        'approver_id',
        'creator_id',
        'creator_type',
        'reason',
        'customer_details',
    ];

    protected $casts = [
        'status' => FinancingOrderStatus::class,
        'approved_at' => 'datetime',
        'data' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
        // Chain fluent methods for configuration options
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(
                'contract'
            )
            ->singleFile(
            );

        $this
            ->addMediaCollection(
                'power_of_attorney'
            )
            ->singleFile(
            );
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

    /**
     * Check if this user requires verifying by OTP based on role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request): bool
    {
        return true;
    }
}
