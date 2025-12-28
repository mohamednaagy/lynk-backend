<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\SystemNotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Summary of UserNotificationSetting
 *
 * @property int $id
 * @property bool $is_enabled
 * @property int $user_id
 * @property User $user
 * @property SystemNotificationType $notification_type
 * @property NotificationChannel $channel
 *
 * @mixin \Illuminate\Database\Eloquent\Builder;
 */
class UserNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'channel',
        'is_enabled',
    ];

    protected $casts = [
        'notification_type' => SystemNotificationType::class,
        'channel' => NotificationChannel::class,
        'is_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
