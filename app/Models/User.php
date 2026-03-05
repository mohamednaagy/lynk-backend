<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Grantify\Contracts\Grantifiable;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Models\AuthorizationToken;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * User Model.
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string|null $national_id
 * @property string|null $phone_country
 * @property-read string $fullName
 * @property-read string $full_name
 * @property \Spatie\Permission\Models\Role[] $roles
 * @property mixed $dummy
 *
 * @method static Builder<User> admin()
 * @method static Builder<User> whereFullNameLike(string $search)
 * @method static Builder<User> lenderAdmin()
 * @method static Builder<User> withLenderAdminForCompany(int $companyId)
 * @method static Builder<User> forTradeRequestCancelledNotification(int $companyId)
 * @method static Builder<User> role(string|array|\Spatie\Permission\Contracts\Role $role, string|null $guard = null)
 * @method static Builder<User> withoutRole(string|array $role)
 *
 * @mixin Builder<User>
 */
class User extends Authenticatable implements Grantifiable, HasLocalePreference, JWTSubject, MustVerifyEmail, Otpifiable
{
    use BelongsToTenant, HasFactory, HasRoles, Notifiable, SoftDeletes;

    const DELETED_MODEL_EMAIL_AND_STRING_SEPARATOR = '@@@';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'email_verified_at',
        'password',
        'locale',
        'company_id',
        'is_active',
        'can_manage_orders',
        'is_auto_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_number' => E164PhoneNumberCast::class,
        'is_active' => 'boolean',
        'can_manage_orders' => 'boolean',
    ];

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? bcrypt($value) : null,
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => "{$this->first_name} {$this->last_name}",
        );
    }

    public function scopeWhereFullNameLike(Builder $query, string $search): Builder
    {
        return $query->whereRaw(
            "CONCAT(first_name, ' ', last_name) LIKE ?",
            ["%{$search}%"]
        );
    }

    protected function mobileDialingPhoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => optional($this->phone_number)->formatForMobileDialingInCountry(optional($this->phone_number)->getCountry()),
        );
    }

    protected function phoneNumberCountryCode(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => optional($this->phone_number)->getCountry(),
        );
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phone_number;
    }

    public function getNationalId(): string
    {
        return $this->national_id ?? '';
    }

    public function routeOtpForPhoneNumber()
    {
        return phone($this->phone_number, $this->phone_country);
    }

    /**
     * Check if this user requires verifying by OTP based on role.
     */
    public function doesRequireVerifyingByOtp(Request $request): bool
    {
        return ! $this->hasRole(Role::LenderApiUser) && ! Cache::get('has_verified_otp_'.$this->id, fn () => false);
    }

    public function authorizationTokens(): HasMany
    {
        return $this->hasMany(AuthorizationToken::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'company_id');
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    public function isRegisterCompleted()
    {
        return ! is_null($this->password);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function notificationSettings(): HasMany
    {
        return $this->hasMany(UserNotificationSetting::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FinancingOrder::class, 'creator_id', 'id');
    }

    public function scopeCompanyType(Builder $query, $type): Builder
    {
        return $query->whereHas('company', function ($query) use ($type) {
            return $query->where('type', $type);
        });
    }

    public function getEmailForSoftDeleting()
    {
        if ($this->deleted_at === null) {
            return Str::random(10).self::DELETED_MODEL_EMAIL_AND_STRING_SEPARATOR.$this->email;
        }

        return $this->email;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Mark the user's email as verified and update the password.
     */
    public function markEmailAsVerifiedAndUpdatePassword(string $password)
    {
        $this->update(['password' => $password, 'email_verified_at' => now()]);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin) || $this->hasRole(Role::Manager);
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }

    /**
     * Get the entity's notifications.
     *
     * @return MorphMany<DatabaseNotification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeAdmin(Builder $query): Builder
    {
        return $query->role(Role::Admin);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeLenderAdmin(Builder $query): Builder
    {
        return $query->role(Role::LenderAdmin);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeWithLenderAdminForCompany(Builder $query, int $companyId): Builder
    {
        return $query->lenderAdmin()
            ->where('company_id', $companyId);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeForTradeRequestCancelledNotification(Builder $query, int $companyId): Builder
    {
        return $query->where(function (Builder $q) use ($companyId) {
            /** @var Builder<User> $q */
            $q->withoutRole(Role::Admin)
                ->where(function (Builder $q) use ($companyId) {
                    /** @var Builder<User> $q */
                    $q->withLenderAdminForCompany($companyId);
                });
        });
    }

    public function routeNotificationForMail()
    {
        if ($this->hasRole([Role::Admin, Role::Manager])) {
            return null;
        }

        return $this->email;
    }
}
