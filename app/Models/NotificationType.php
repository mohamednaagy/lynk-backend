<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationType extends Model
{
    protected $fillable = ['name'];

    public function userSettings(): HasMany
    {
        return $this->hasMany(UserNotificationSetting::class);
    }
}
