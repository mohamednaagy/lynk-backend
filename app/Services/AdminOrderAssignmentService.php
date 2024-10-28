<?php

namespace App\Services;

use App\Models\FinancingOrder;
use App\Settings\Classes\GeneralSettings;
use App\Models\User;

class AdminOrderAssignmentService
{
    protected GeneralSettings $settings;

    public function __construct(GeneralSettings $settings)
    {
        $this->settings = $settings;
    }
    /**
     * Retrieve the list of order responsible admins from cache or database.
     *
     * @return array
     */
    public function getOrderResponsibleAdmins(): array
    {
        return $this->settings->order_responsible_admins ?? [];
    }

    /**
     * Update the list of order responsible admins in the database and cache.
     *
     * @param array $admins
     * @return void
     */
    public function updateOrderResponsibleAdmins(array $admins): void
    {
        $this->settings->order_responsible_admins = $admins;
        $this->settings->save();
    }

    /**
     * Remove an admin from the order responsible list.
     *
     * @param Admin $admin
     * @return void
     */
    public function removeAdmin(User $admin): void
    {
        $admins = $this->settings->order_responsible_admins;
        $admins = array_values(array_diff($admins, [$admin->id]));
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
        $admins = $this->settings->order_responsible_admins;
        if (!in_array($admin->id, $admins)) {
            $admins[] = $admin->id; 
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
        $admins = $this->getOrderResponsibleAdmins();

        if (empty($admins)) {
            return null;
        }
        
        $nextAdmin = array_shift($admins);
        $admins[] = $nextAdmin;

        $this->updateOrderResponsibleAdmins($admins);

        return $nextAdmin;
    }


    public function assignNextAdminToFinancingOrder(FinancingOrder $financingOrder): void
    {
        $financingOrder->assignable_id = $this->reOrderResponsableAdmins();
        $financingOrder->save();
    }
}
