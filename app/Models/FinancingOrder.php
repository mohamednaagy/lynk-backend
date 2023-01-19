<?php

namespace App\Models;

use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Support\Money\Casts\MoneyStringCast;
use App\Support\QueryScoper\HasScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property mixed $status
 * @property mixed $traderOrders
 * @property mixed $currency
 * @property mixed $amount
 * @property mixed $selling_price
 * @property mixed $id
 */
class FinancingOrder extends Model implements HasMedia, Otpifiable
{
    use HasFactory;
    use InteractsWithMedia;
    use BelongsToTenant;
    use LogsActivity;
    use HasScopes;

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
        'customer_details',
        'status_reason',
        'client_wakala_accepted_at',
        'is_verification_required',
        'company_id',
        'created_at',
    ];

    protected $casts = [
        'status' => FinancingOrderStatus::class,
        'approved_at' => 'datetime',
        'client_wakala_accepted_at' => 'datetime',
        'data' => 'array',
        'customer_details' => 'array',
        'phone_number' => E164PhoneNumberCast::class,
        'amount' => MoneyStringCast::class.':currency',
        'selling_price' => MoneyStringCast::class.':currency',
    ];

    protected function isUpdatable(): Attribute
    {
        return Attribute::get(
            fn () => $this->status->canBeUpdated(),
        );
    }

    protected function phoneNumberCountryCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phone_number->getCountry(),
        );
    }

    protected function mobileDialingPhoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phone_number->formatForMobileDialingInCountry($this->phone_number->getCountry()),
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
            ->addMediaCollection(FinancingOrderMediaCollection::ClientWakala)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::LenderWakala)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::Contract)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::PowerOfAttorney)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::PromiseToPurchase)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::MurabahaPurchaseOrder)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::TransferOwnershipToLender)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::SellingCommodityToCustomer)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::TtiHoldingCertificate)
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
        return $this->getFirstMediaUrl(FinancingOrderMediaCollection::PowerOfAttorney);
    }

    public function getContractAttribute()
    {
        return $this->getFirstMediaUrl(FinancingOrderMediaCollection::Contract);
    }

    public function traderOrders()
    {
        return $this->hasMany(TraderOrder::class, 'financing_order_id', 'id');
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phone_number;
    }

    public function getNationalId(): string
    {
        return $this->national_id;
    }

    /**
     * Check if this user requires verifying by OTP based on role.
     *
     * @param  Request  $request
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request): bool
    {
        return true;
    }

    public function scopeCancelled($query)
    {
        return $query->whereStatus(FinancingOrderStatus::Cancelled);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn(
            'status',
            [
                FinancingOrderStatus::Cancelled,
                FinancingOrderStatus::Completed,
                FinancingOrderStatus::Rejected,
            ]
        );
    }

    public function scopeRequireAction($query)
    {
        return $query->whereIn(
            'status',
            FinancingOrderStatus::$requireActionStatuses
        );
    }

    public function scopeCompleted($query)
    {
        return $query->whereStatus(FinancingOrderStatus::Completed);
    }

    public function scopeRejected($query)
    {
        return $query->whereStatus(FinancingOrderStatus::Rejected);
    }

    public function scopeByCreator($query, Model $model)
    {
        $query->whereHasMorph(
            'creator',
            $model->getMorphClass(),
            function ($query) use ($model) {
                $query->where('creator_id', $model->getKey());
            }
        );
    }

    public function activeTraderOrder()
    {
        return $this->traderOrders()->where('status', TraderOrderStatus::InProgress)->latest();
    }
}
