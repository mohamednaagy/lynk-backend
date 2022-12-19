<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use App\Support\QueryScoper\HasScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use Spatie\Permission\Models\Role;

class Enquiry extends Model
{
    use HasFactory, SoftDeletes, HasScopes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subject',
        'body',
        'status',
        'name',
        'email',
        'phone_number',
        'user_id',
        'role_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => EnquiryStatus::class,
        'phone_number' => E164PhoneNumberCast::class,
    ];

    protected function phoneNumberCountryCode(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => "{$this->phone_number->getCountry()}",
        );
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(EnquiryReply::class);
    }
}
