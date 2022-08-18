<?php

namespace Modules\Customers\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customers\Actions\UpdateCustomerWithRoleAndPermissionAction;
use Modules\Customers\Actions\CreateCustomerWithRoleAndPermissionAction;
use Modules\Customers\Actions\Contracts\CreateCustomerWithRoleAndPermission;
use Modules\Customers\Actions\Contracts\UpdateCustomerWithRoleAndPermission;

class ActionsServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CreateCustomerWithRoleAndPermission::class => CreateCustomerWithRoleAndPermissionAction::class,
        UpdateCustomerWithRoleAndPermission::class => UpdateCustomerWithRoleAndPermissionAction::class
    ];
}
