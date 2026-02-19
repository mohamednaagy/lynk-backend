<?php

namespace App\Models;

use App\Enums\Area;
use App\Enums\FinancingOrderBorrowerTypeEnum;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\FinancingOrderTypeEnum;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Role;
use App\Enums\Trader;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Enums\TransactionReason;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Money\Casts\MoneyStringCast;
use App\Support\QueryScoper\HasScopes;
use App\Support\Traders\Traits\TraderHelperTrait;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Otpify\Contracts\Otpifiable;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property FinancingOrderStatus $status
 * @property mixed $traderOrders
 * @property mixed $currency
 * @property mixed $amount
 * @property mixed $selling_price
 * @property int $id
 * @property PhoneNumber|null $phone_number
 * @property ?string $latest_activity
 * @property ?MurabhaStep $current_step
 * @property-read CommodityType|null $commodityType
 *
 * @mixin Builder<FinancingOrder>
 */
class FinancingOrder extends Model implements HasMedia, Otpifiable
{
    use BelongsToTenant;
    use HasCreator;
    use HasFactory;
    use HasScopes;
    use InteractsWithMedia;
    use TraderHelperTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
        'customer_name',
        'status_reason',
        'is_verification_required',
        'company_id',
        'created_at',
        'charged_trader_orders_count',
        'commodity_type_id',
        'type',
        'borrower_identifier',
        'lender_type',
        'lender_identifier',
        'borrower_type',
        'cost_with_vat',
        'cost_without_vat',
        'latest_activity',
    ];

    protected $casts = [
        'status' => FinancingOrderStatus::class,
        'approved_at' => 'datetime',
        'data' => 'array',
        'is_verification_required' => 'boolean',
        'phone_number' => E164PhoneNumberCast::class,
        'amount' => MoneyStringCast::class.':currency',
        'selling_price' => MoneyStringCast::class.':currency',
        'type' => FinancingOrderTypeEnum::class,
    ];

    protected function currentStep(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    $traderOrder = $this->activeTraderOrder->first();
                    if (is_null($traderOrder)) {
                        Log::error(
                            "No active trader order found for financing_order_id => {$this->id}",
                            [
                                'financingOrderId' => $this->id,
                                'reference_number' => $this->reference_number,
                            ]
                        );

                        return null;
                    }
                    $stepDictionary = new StepHistoriesDictionary(
                        $traderOrder->provider,
                        $traderOrder->version,
                        $traderOrder->contract_signed_type
                    );

                    $currentStepNode = $stepDictionary->getStepByHistory($traderOrder->last_history_action);
                    if (is_null($currentStepNode)) {
                        Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(
                            formatLogTitle("No step node found for order {$this->id} with history action {$traderOrder->last_history_action}", $traderOrder),
                            [
                                'financingOrderId' => $this->id,
                                'traderOrderId' => $traderOrder->id,
                                'reference_number' => $this->reference_number,
                                'last_history_action' => $traderOrder->last_history_action,
                            ]
                        );

                        return null;
                    }

                    return MurabhaStep::fromValue($currentStepNode->step);
                } catch (\Exception $e) {
                    $traderOrder = $this->activeTraderOrder->first();
                    $logMessage = "Error retrieving current step for order {$this->id}: ".$e->getMessage();
                    if ($traderOrder) {
                        $logMessage .= ' trader_order_id => '.$traderOrder->id;
                    }
                    Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(
                        formatLogTitle($logMessage, $traderOrder),
                        [
                            'financingOrderId' => $this->id,
                            'traderOrderId' => $traderOrder->id,
                            'reference_number' => $this->reference_number,
                        ]
                    );

                    return null;
                }
            }
        );
    }

    /**
     * @return Attribute<bool, $this>
     */
    protected function isUpdatable(): Attribute
    {
        return Attribute::get(
            fn () => $this->status->canBeUpdated(),
        );
    }

    /**
     * @return Attribute<string|null, $this>
     */
    protected function phoneNumberCountryCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phone_number?->getCountry(),
        );
    }

    /**
     * @return Attribute<string, $this>
     */
    protected function mobileDialingPhoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phone_number?->formatForMobileDialingInCountry($this->phoneNumberCountryCode),
        );
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id', 'id');
    }

    public function getPowerOfAttorneyAttribute(): string
    {
        return $this->getFirstMediaUrl(FinancingOrderMediaCollection::PowerOfAttorney);
    }

    public function getContractAttribute(): string
    {
        return $this->getFirstMediaUrl(FinancingOrderMediaCollection::Contract);
    }

    /**
     * @return HasMany<TraderOrder, $this>
     */
    public function traderOrders(): HasMany
    {
        return $this->hasMany(TraderOrder::class, 'financing_order_id', 'id');
    }

    /**
     * @return HasMany<FinancingOrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(FinancingOrderStatusHistory::class, 'order_id', 'id');
    }

    /**
     * @return HasOne<FinancingOrderStatusHistory, $this>
     */
    public function latestStatusHistory(): HasOne
    {
        return $this->hasOne(FinancingOrderStatusHistory::class, 'order_id', 'id')->latestOfMany();
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function creationFeeTransactions(): HasMany
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

    public function scopeReadyForProcessing($query)
    {
        $providersWithVersions = [
            [
                'provider' => Trader::Dmcc,
                'versions' => ['v1'],
            ],
            [
                'provider' => Trader::FakeDmcc,
                'versions' => ['v1'],
            ],
            [
                'provider' => Trader::Bursam,
                'versions' => ['v2'],
            ],
            [
                'provider' => Trader::Lynk,
                'versions' => ['v1'],
            ],
        ];

        return $query
            ->where('status', FinancingOrderStatus::Approved)
            ->withCount(['traderOrders' => function ($query) use ($providersWithVersions) {
                $query->where(function ($subQuery) use ($providersWithVersions) {
                    $isFirstLoopComplete = false;

                    foreach ($providersWithVersions as $providerWithVersions) {
                        $whereClosure = function ($q) use ($providerWithVersions) {
                            return $q->where('provider', $providerWithVersions['provider'])
                                ->whereIn('version', $providerWithVersions['versions']);
                        };

                        if ($isFirstLoopComplete) {
                            $subQuery->orWhere($whereClosure);
                        } else {
                            $subQuery->where($whereClosure);
                            $isFirstLoopComplete = true;
                        }
                    }
                })
                    ->whereIn('status', [
                        TraderOrderStatus::InProgress,
                    ]);
            }])
            ->whereRelation('lender.lenderDetail', 'trading_mode', TraderOrderMode::Automatic)
            ->having('trader_orders_count', 0);
    }

    public function scopeByCreator($query, Model $model): Builder
    {
        return $query->where('creator_id', $model->getKey());
    }

    /**
     * @return HasMany<TraderOrder, $this>
     */
    public function activeTraderOrder(): HasMany
    {
        return $this->traderOrders()
            ->where(function ($query) {
                $query->where('status', TraderOrderStatus::InProgress)->orWhere('status', TraderOrderStatus::Initiated);
            })
            ->latest();
    }

    /**
     * @return HasOne<TraderOrder, $this>
     */
    public function latestTraderOrder(): HasOne
    {
        return $this->hasOne(TraderOrder::class, 'financing_order_id', 'id')->latestOfMany();
    }

    /**
     * @return HasMany<TraderOrder, $this>
     */
    public function initiatedTraderOrders(): HasMany
    {
        return $this->traderOrders()
            ->where('status', TraderOrderStatus::Initiated)
            ->latest();
    }

    /**
     * @return HasMany<TraderOrder, $this>
     */
    public function holdTraderOrders(): HasMany
    {
        return $this->traderOrders()
            ->where('status', TraderOrderStatus::Hold)
            ->latest();
    }

    public function canBeCompleted(?string $area = Area::SuperAdmin): bool
    {

        $allTraderOrders = $this->traderOrders();
        $traderOrderCompleted = ($area == Area::Lender)
        ? $allTraderOrders->get()->some(fn (TraderOrder $traderOrder) => $traderOrder->canBeMarkedAsCompleted($area))
        : $allTraderOrders->completed()->exists();

        return $traderOrderCompleted
            && $this->status->isNot(FinancingOrderStatus::Completed)
            && $this->status->isNot(FinancingOrderStatus::Cancelled);

    }

    public function cantBeCompleted(): bool
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
            || $this->hasHoldTraderOrder()

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

    public function hasHoldTraderOrder(): bool
    {
        return $this->traderOrders()->where('status', TraderOrderStatus::Hold)->exists();
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
        return $this->lender?->lenderDetail->trading_mode->is($mode);
    }

    public function isDefaultTraderAvailable(): bool
    {
        return true;
    }

    public function isCancellable(string $area): bool
    {
        $canMoveToPendingCancellation = $this->status->canMoveTo(FinancingOrderStatus::PendingCancellation);

        if ($canMoveToPendingCancellation === false) {
            return false;
        }

        if ($area === Area::Lender && $this->traderOrders->every(fn ($traderOrder) => $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::DeliveryConfirmed))) {
            return false;
        }

        $this->refresh();

        return $this->activeTraderOrder->every(fn (TraderOrder $traderOrder) => $traderOrder->isCancellable($area));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsableAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignable_id', 'id')->withTrashed();
    }

    public function retry(): void
    {
        $this->updateOrderStatus($this, FinancingOrderStatus::PendingTraderOrder);
    }

    /**
     * @return BelongsTo<CommodityType, $this>
     */
    public function commodityType(): BelongsTo
    {
        return $this->belongsTo(CommodityType::class);
    }

    public function getPreferredTrader(): string
    {
        if ($this->commodity_type_id) {
            return $this->commodityType->provider->value;
        } else {
            if ($this->lender->isInternationalMarketType()) {
                return Trader::Bursam;
            }

            return Trader::Lynk;
        }
    }

    /**
     * @return Attribute<string|null, $this>
     */
    protected function customerName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getBorrowerName(),
            set: fn ($value) => ['borrower_identifier' => $value],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getLenderInfo(): array
    {
        return [
            'id' => $this->lender_identifier,
            'type' => $this->lender_type,
            'name' => $this->getLenderName(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getBorrowerInfo(): array
    {
        return [
            'type' => $this->borrower_type,
            'name' => $this->getBorrowerName(),
        ];
    }

    public function getLenderName(): ?string
    {
        return Lender::withTrashed()->find($this->lender_identifier)->name ?? null;
    }

    public function getBorrowerName(): ?string
    {
        if ($this->borrower_type == FinancingOrderBorrowerTypeEnum::Lender) {
            return $this->lender->name;
        }

        return $this->borrower_identifier;
    }

    public function addToFinancingOrderCosts(float $costWithVat, float $costWithoutVat): void
    {
        $this->cost_with_vat += $costWithVat;
        $this->cost_without_vat += $costWithoutVat;
        $this->save();
    }

    public function subtractFromFinancingOrderCosts(float $costWithVat, float $costWithoutVat): void
    {
        $this->cost_with_vat -= $costWithVat;
        $this->cost_without_vat -= $costWithoutVat;
        $this->save();
    }

    /**
     * @return BelongsTo<Lender, $this>
     */
    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class, 'company_id')->withTrashed();
    }
}
