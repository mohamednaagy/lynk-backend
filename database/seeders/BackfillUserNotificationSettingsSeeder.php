<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\NotificationPreferenceService;
use Illuminate\Database\Seeder;
use Stancl\Tenancy\Database\TenantScope;

class BackfillUserNotificationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(NotificationPreferenceService::class);

        User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->chunk(500, function ($users) use ($service) {
                foreach ($users as $user) {
                    $service->ensureDefaults($user);
                }
            });
    }
}
