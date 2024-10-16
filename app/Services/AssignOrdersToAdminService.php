<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssignOrdersToAdminService
{
    public function getAssignAdminToOrdersSorting()
    {
        return Cache::remember('assign_admin_to_orders_sorting', 60, function () {
            $setting = DB::table('settings')->where('name', 'assign_admin_to_orders_sorting')->first();
            return json_decode($setting->payload, true) ?? [];
        });
    }

    public function updateAssignAdminToOrdersSorting($value)
    {
        DB::table('settings')->where('name', 'assign_admin_to_orders_sorting')->update(['payload' => json_encode($value)]);
        Cache::put('assign_admin_to_orders_sorting', $value, 60);
    }

    public function removeAdmin($adminId)
    {
        $sorting = $this->getAssignAdminToOrdersSorting();
        $sorting = array_values(array_diff($sorting, [$adminId])); // Remove the admin
        $this->updateAssignAdminToOrdersSorting($sorting);
    }

    public function addAdmin($adminId)
    {
        $sorting = $this->getAssignAdminToOrdersSorting();
        if (!in_array($adminId, $sorting)) {
            $sorting[] = $adminId; // Add the admin
        }
        $this->updateAssignAdminToOrdersSorting($sorting);
    }

    public function assignOrderToAdmin($adminId)
    {
        $sorting = $this->getAssignAdminToOrdersSorting();
        // Move admin to the front
        $sorting = array_values(array_diff($sorting, [$adminId])); // Remove
        array_unshift($sorting, $adminId); // Add to front
        $this->updateAssignAdminToOrdersSorting($sorting);
    }
}
