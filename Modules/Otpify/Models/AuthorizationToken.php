<?php

namespace Modules\Otpify\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuthorizationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'token', 'area', 'device_details'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
