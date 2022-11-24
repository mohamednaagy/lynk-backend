<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Grantify\Contracts\Grantifiable;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Models\AuthorizationToken;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @method static create(array $data)
 */
class User extends Authenticatable implements Otpifiable, Grantifiable, MustVerifyEmail, HasLocalePreference
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, BelongsToTenant;

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
    ];

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
     *
     * @param  Request  $request
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request): bool
    {
        return false;
    }

    /**
     * @return HasMany
     */
    public function authorizationTokens(): HasMany
    {
        return $this->hasMany(AuthorizationToken::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return HasMany
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }
}
