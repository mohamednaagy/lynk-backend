<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'email_enabled',
        'portal_enabled',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
