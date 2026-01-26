<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\SystemNotificationType;
use App\Models\UserNotificationSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        UserNotificationSetting::where('notification_type', SystemNotificationType::ORDER_CANCELLED->value)
            ->whereHas('user', function ($query) {
                $query->role(Role::Manager);
            })
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
