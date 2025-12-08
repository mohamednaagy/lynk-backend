<?php

namespace Database\Seeders;

use App\Enums\SystemNotificationType;
use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SystemNotificationType::cases() as $case) {
            NotificationType::firstOrCreate(['name' => $case->value]);
        }
    }
}
