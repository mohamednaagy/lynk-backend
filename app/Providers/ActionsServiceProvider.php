<?php

namespace App\Providers;

use App\Actions\AssignPermissionToUserAction;
use App\Actions\AssignRoleToUserAction;
use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateAdminWithRoleAndPermission;
use App\Actions\Contracts\CreateCompany;
use App\Actions\Contracts\CreateCustomerWithRoleAndPermission;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\FindUserByIdAndRole;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Actions\Contracts\GetSettingsArea;
use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\ListSettings;
use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\RegisterLender;
use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Actions\Contracts\UpdateCustomerWithRoleAndPermission;
use App\Actions\Contracts\UpdateSettings;
use App\Actions\Contracts\UpdateUser;
use App\Actions\CreateAdminWithRoleAndPermissionAction;
use App\Actions\CreateCompanyAction;
use App\Actions\CreateCustomerWithRoleAndPermissionAction;
use App\Actions\CreateUserAction;
use App\Actions\FindUserByIdAndRoleAction;
use App\Actions\GetPaginatedUsersByRoleAction;
use App\Actions\GetSettingsAreaAction;
use App\Actions\GetSettingsClassInstanceAction;
use App\Actions\Lenders\CreateLenderUserWithRoleAndPermissionAction;
use App\Actions\ListSettingsAction;
use App\Actions\LoginUserAction;
use App\Actions\RegisterLenderAction;
use App\Actions\SyncPermissionToUserAction;
use App\Actions\SyncRoleToUserAction;
use App\Actions\UpdateAdminWithRoleAndPermissionAction;
use App\Actions\UpdateCustomerWithRoleAndPermissionAction;
use App\Actions\UpdateSettingsAction;
use App\Actions\UpdateUserAction;
use Illuminate\Support\ServiceProvider;

class ActionsServiceProvider extends ServiceProvider
{
    public array $bindings = [
        LoginUser::class => LoginUserAction::class,
        RegisterLender::class => RegisterLenderAction::class,

        CreateUser::class => CreateUserAction::class,
        CreateCompany::class => CreateCompanyAction::class,
        AssignRoleToUser::class => AssignRoleToUserAction::class,
        AssignPermissionToUser::class => AssignPermissionToUserAction::class,

        UpdateUser::class => UpdateUserAction::class,
        SyncRoleToUser::class => SyncRoleToUserAction::class,
        SyncPermissionToUser::class => SyncPermissionToUserAction::class,

        FindUserByIdAndRole::class => FindUserByIdAndRoleAction::class,
        GetPaginatedUsersByRole::class => GetPaginatedUsersByRoleAction::class,

        CreateAdminWithRoleAndPermission::class => CreateAdminWithRoleAndPermissionAction::class,
        UpdateAdminWithRoleAndPermission::class => UpdateAdminWithRoleAndPermissionAction::class,

        CreateCustomerWithRoleAndPermission::class => CreateCustomerWithRoleAndPermissionAction::class,
        UpdateCustomerWithRoleAndPermission::class => UpdateCustomerWithRoleAndPermissionAction::class,

        CreateLenderUserWithRoleAndPermission::class => CreateLenderUserWithRoleAndPermissionAction::class,

        ListSettings::class => ListSettingsAction::class,
        UpdateSettings::class => UpdateSettingsAction::class,
        GetSettingsArea::class => GetSettingsAreaAction::class,
        GetSettingsClassInstance::class => GetSettingsClassInstanceAction::class,
    ];
}
