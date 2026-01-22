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
        UserNotificationSetting::where('notification_type', SystemNotificationType::ORDER_APPROVED->value)
            ->whereHas('user', function ($query) {
                $query->role(Role::LenderBilling);
            })
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation as data is deleted and the code is updated
    }
};
