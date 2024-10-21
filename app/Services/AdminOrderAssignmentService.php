<?php

namespace App\Services;

use App\Models\FinancingOrder;
use App\Settings\Classes\GeneralSettings;
use App\Models\User; // Make sure to import the Admin model
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminOrderAssignmentService
{
    private const CACHE_KEY = 'order_responsible_admins';
    private const CACHE_DURATION = 60; // Cache duration in seconds

    /**
     * Retrieve the list of order responsible admins from cache or database.
     *
     * @return array
     */
    public function getOrderResponsibleAdmins(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            $setting = app(GeneralSettings::class)->order_responsible_admins;
            return $setting ?: [];
        });
    }

    /**
     * Update the list of order responsible admins in the database and cache.
     *
     * @param array $admins
     * @return void
     */
    public function updateOrderResponsibleAdmins(array $admins): void
    {
        DB::table('settings')
            ->where('name', self::CACHE_KEY)
            ->update(['payload' => json_encode($admins)]);

        Cache::put(self::CACHE_KEY, $admins, self::CACHE_DURATION);
    }

    /**
     * Remove an admin from the order responsible list.
     *
     * @param Admin $admin
     * @return void
     */
    public function removeAdmin(User $admin): void
    {
        $admins = $this->getOrderResponsibleAdmins();
        $admins = array_shift($admins); // Remove the admin
        $this->updateOrderResponsibleAdmins($admins);
    }

    /**
     * Add an admin to the order responsible list.
     *
     * @param Admin $admin
     * @return void
     */
    public function addAdmin(User $admin): void
    {
        $admins = $this->getOrderResponsibleAdmins();
        if (!in_array($admin->id, $admins, true)) {
            $admins[] = $admin->id; // Add the admin
            $this->updateOrderResponsibleAdmins($admins);
        }
    }

    /**
     * Reorder the responsible admins and move a specific admin to the front.
     *
     * @param User $admin
     * @return void
     */
    public function reOrderResponsableAdmins(): ?int
    {
        $service = new self();
        $admins = $service->getOrderResponsibleAdmins();

        if (empty($admins)) {
            return null;
        }

        $nextAdmin = array_shift($admins);
        $admins[] = $nextAdmin;

        $service->updateOrderResponsibleAdmins($admins);

        return $nextAdmin;
    }


    public static function assignNextAdminToFinancingOrder(FinancingOrder $financingOrder): void
    {
        $financingOrder->assignable_id = (new self())->reOrderResponsableAdmins();
        $financingOrder->saveQuietly();
    }
}
