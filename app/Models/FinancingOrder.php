<?php

namespace App\Models;

use App\Enums\Area;
use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Role;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Enums\TransactionReason;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Money\Casts\MoneyStringCast;
use App\Support\QueryScoper\HasScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    use BelongsToTenant;
    use HasFactory;
    use HasScopes;
    use InteractsWithMedia;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_number',
        'national_id',
        'contract_number',
        'phone_number',
        'amount',
        'selling_price',
        'status',
        'approved_at',
        'approver_id',
        'creator_id',
        'creator_type',
        'customer_name',
        'customer_details',
        'status_reason',
        'is_verification_required',
        'company_id',
        'created_at',
    ];

    protected $casts = [
        'status' => FinancingOrderStatus::class,
        'approved_at' => 'datetime',
        'data' => 'array',
        'is_verification_required' => 'boolean',
        'customer_details' => 'array',
        'phone_number' => E164PhoneNumberCast::class,
        'amount' => MoneyStringCast::class.':currency',
        'selling_price' => MoneyStringCast::class.':currency',
    ];

    protected function currentStep(): Attribute
    {
        return Attribute::make(
            get: function () {
                $traderOrder = $this->activeTraderOrder->first();

                if (is_null($traderOrder) || is_null($traderOrder->last_history_action)) {
                    return null;
                }

                $currentStepNode = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
                    ->getStepByHistory($traderOrder->last_history_action);

                return MurabhaStep::fromValue($currentStepNode->step);
            }
        );
    }

    protected function isUpdatable(): Attribute
    {
        return Attribute::get(
            fn () => $this->status->canBeUpdated(),
        );
    }

    protected function phoneNumberCountryCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phone_number?->getCountry(),
        );
    }

    protected function mobileDialingPhoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phone_number?->formatForMobileDialingInCountry($this->phone_number?->getCountry()),
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
            ->addMediaCollection(FinancingOrderMediaCollection::Contract)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::PowerOfAttorney)
            ->singleFile();
        $this
            ->addMediaCollection(FinancingOrderMediaCollection::PaymentProofFromLenderToCustomer)
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

    public function creationFeeTransactions()
    {
        return $this->hasMany(Transaction::class, 'meta->financing_order_id')
            ->where('reason', TransactionReason::OrderCreationFee);
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    public function getNationalId(): string
    {
        return $this->national_id;
    }

    /**
     * Check if this user requires verifying by OTP based on role.
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

    public function activeTraderOrder(): HasMany
    {
        return $this->traderOrders()
            ->where(function ($query) {
                $query->where('status', TraderOrderStatus::InProgress)->orWhere('status', TraderOrderStatus::Initiated);
            })
            ->latest();
    }

    public function latestTraderOrder(): HasOne
    {
        return $this->hasOne(TraderOrder::class, 'financing_order_id', 'id')->latestOfMany();
    }

    public function initiatedTraderOrders(): HasMany
    {
        return $this->traderOrders()
            ->where('status', TraderOrderStatus::Initiated)
            ->latest();
    }

    public function canBeCompleted(?string $area = Area::SuperAdmin): bool
    {
        $allTraderOrders = $this->traderOrders();
        $traderOrderCompleted = ($area == Area::Lender) ? $allTraderOrders->completedWithContractSignedType() : $allTraderOrders->completed();
        return $traderOrderCompleted->exists()
            && $this->status->isNot(FinancingOrderStatus::Completed)
            && $this->status->isNot(FinancingOrderStatus::Cancelled);
    }

    public function cantBeCompleted()
    {
        return ! $this->canBeCompleted();
    }

    public function canCreateTraderOrder(?User $user = null): bool
    {
        if (
            $this->isNotReadyToStartTrading()
            || $this->hasFailed()
            || $this->isComplete()
            || $this->isInCancellationState()
            || $this->isDefaultTraderAvailable() === false
            || $this->isInPendingTradingRequestState() === false
            || $this->hasCompletedTraderOrder()

        ) {
            return false;
        }
        $currentUserHasPermissionToCreate = $user?->hasRole([Role::Admin, Role::Manager])
            || $this->isTradingMode(TraderOrderMode::Automatic);

        return $currentUserHasPermissionToCreate;
    }

    public function hasCompletedTraderOrder(): bool
    {
        return $this->traderOrders()->where('status', TraderOrderStatus::Completed)->exists();
    }

    private function isComplete(): bool
    {
        return $this->status->is(FinancingOrderStatus::Completed);
    }

    private function isNotReadyToStartTrading(): bool
    {
        return $this->status->is(FinancingOrderStatus::PendingApproval)
            || $this->status->is(FinancingOrderStatus::Rejected);
    }

    private function hasFailed(): bool
    {
        return $this->status->is(FinancingOrderStatus::TradingFailure);
    }

    private function isInPendingTradingRequestState(): bool
    {
        $doesHaveActiveOrder = $this->traderOrders()
            ->whereIn('status', [TraderOrderStatus::Initiated, TraderOrderStatus::InProgress, TraderOrderStatus::PendingCancellation])
            ->exists();

        if ($doesHaveActiveOrder) {
            return false;
        }

        return $this->status->is(FinancingOrderStatus::PendingTraderOrder)
            || $this->status->is(FinancingOrderStatus::Approved)
            || $this->status->is(FinancingOrderStatus::InProgress);
    }

    private function isInCancellationState(): bool
    {
        return $this->status->is(FinancingOrderStatus::PendingCancellation)
            || $this->status->is(FinancingOrderStatus::Cancelled);
    }

    public function isInPendingCancellationState(): bool
    {
        return $this->status->is(FinancingOrderStatus::PendingCancellation);
    }

    public function isTradingMode(TraderOrderMode|string $mode)
    {
        return $this->company->trading_mode->is($mode);
    }

    public function isDefaultTraderAvailable()
    {
        if (config('trader.default') === 'bursam') {
            return is_bursam_service_available();
        }

        return true;
    }

    public function isCancellable($area)
    {
        $canMoveToPendingCancellation = $this->status->canMoveTo(FinancingOrderStatus::PendingCancellation);

        if ($canMoveToPendingCancellation === false) {
            return false;
        }

        if ($area === Area::Lender && $this->traderOrders()->completedWithContractSignedType(ContractSignedType::Delivery)->exists()) {
            return false;
        }

        $this->refresh();

        return $this->activeTraderOrder->every(fn ($traderOrder) => $traderOrder->isCancellable($area));
    }
}
