<?php

use App\Enums\SystemNotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $typeId = DB::table('notification_types')
            ->where('name', SystemNotificationType::DELIVERY_CONFIRMATION_RECEIVED)
            ->value('id');

        if ($typeId) {
            DB::table('user_notification_settings')
                ->where('notification_type_id', $typeId)
                ->whereIn('user_id', function ($q) {
                    $q->select('id')->from('users')->whereNotNull('company_id');
                })
                ->delete();
        }
    }

    public function down(): void {}
};

