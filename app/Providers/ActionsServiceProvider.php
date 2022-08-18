<?php

namespace App\Providers;

use App\Actions\CreateUserAction;
use App\Actions\UpdateUserAction;
use App\Actions\Contracts\UpdateUser;
use App\Actions\Contracts\CreateUser;
use App\Actions\SyncRoleToUserAction;
use Illuminate\Support\ServiceProvider;
use App\Actions\AssignRoleToUserAction;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\FindUserByIdAndRoleAction;
use App\Actions\SyncPermissionToUserAction;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\AssignPermissionToUserAction;
use App\Actions\Contracts\FindUserByIdAndRole;
use App\Actions\GetPaginatedUsersByRoleAction;
use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\GetPaginatedUsersByRole;

class ActionsServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CreateUser::class => CreateUserAction::class,
        AssignRoleToUser::class => AssignRoleToUserAction::class,
        AssignPermissionToUser::class => AssignPermissionToUserAction::class,

        UpdateUser::class => UpdateUserAction::class,
        SyncRoleToUser::class => SyncRoleToUserAction::class,
        SyncPermissionToUser::class => SyncPermissionToUserAction::class,

        FindUserByIdAndRole::class => FindUserByIdAndRoleAction::class,
        GetPaginatedUsersByRole::class => GetPaginatedUsersByRoleAction::class,
    ];
}
