<?php

namespace Modules\Permission\Contracts;

interface Grantifiable
{
    public function getAllPermissions();
    public function givePermissionTo($permission);
    public function assignRole($role);
    public function syncPermissions($permission);
    public function syncRoles($role);
}
