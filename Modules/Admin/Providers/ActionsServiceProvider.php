<?php

namespace Modules\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Actions\CreateAdminWithRoleAndPermissionAction;
use Modules\Admin\Actions\UpdateAdminWithRoleAndPermissionAction;
use Modules\Admin\Actions\Contracts\CreateAdminWithRoleAndPermission;
use Modules\Admin\Actions\Contracts\UpdateAdminWithRoleAndPermission;

class ActionsServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CreateAdminWithRoleAndPermission::class => CreateAdminWithRoleAndPermissionAction::class,
        UpdateAdminWithRoleAndPermission::class => UpdateAdminWithRoleAndPermissionAction::class
    ];
}
