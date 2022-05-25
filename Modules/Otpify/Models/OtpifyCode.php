<?php

namespace Modules\Otpify\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OtpifyCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'otp_code',
        'expiration_date',
        'expired_at',
        'data'
    ];

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

    public function getVid()
    {
        return $this->id;
    }
}
