<?php

namespace Modules\Permission\Contracts;

interface GrantifyDriverInterface
{
    /**
     * getRolePermissions
     *
     * @param  string $roleName
     * @return array
     */
    public function getRolePermissions(string $roleName): array;

    /**
     * getCustomerPermissions
     *
     * @param  Grantifiable $grantifiable
     * @return array
     */
    public function getGrantifiablePermissions(Grantifiable $grantifiable): array;
}
