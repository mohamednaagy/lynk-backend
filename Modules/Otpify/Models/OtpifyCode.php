<?php

namespace Modules\Otpify\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\VirtualColumn\VirtualColumn;

/**
 * @property mixed $expiration_date
 * @property mixed $otp_code
 * @property mixed $expired_at
 * @property int $otpifiable_id
 */
class OtpifyCode extends Model
{
    use HasFactory, VirtualColumn;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $guarded = [];

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'expired_at' => 'datetime',
        'expiration_date' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'initiator_id',
            'initiator_type',
            'otpifiable_id',
            'otpifiable_type',
            'otp_code',
            'expiration_date',
            'expired_at',
            'created_at',
            'updated_at',
        ];
    }

    protected function otpCode(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? bcrypt($value) : null,
        );
    }

    public function getVid()
    {
        return $this->id;
    }

    public function otpifiable()
    {
        return $this->morphTo('otpifiable');
    }
}
