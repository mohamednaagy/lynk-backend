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
 * @method static create(array $data)
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
     * @var array<int, string>
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
        return $this->national_id;
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin) || $this->hasRole(Role::Manager);
    }
}
