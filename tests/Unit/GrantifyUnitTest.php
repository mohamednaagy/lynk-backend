<?php

namespace Tests\Unit;

use Exception;
use App\Enums\Area;
use Tests\TestCase;
use App\Enums\Role;
use App\Enums\Action;
use App\Enums\Subject;
use App\Actions\SyncRoleToUserAction;
use App\Actions\AssignRoleToUserAction;
use Modules\Permission\Facades\Grantify;
use App\Actions\SyncPermissionToUserAction;
use App\Actions\AssignPermissionToUserAction;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permission\Exceptions\RoleNotFoundException;

class GrantifyUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_role_to_user()
    {
        $assignRoleToUser = new AssignRoleToUserAction();
        $assignRoleToUser->handle($this->user, Role::Customer);

        $this->assertTrue($this->user->hasRole(Role::Customer));
    }

    public function test_assign_Wrong_role_to_user()
    {
        $assignRoleToUser = new AssignRoleToUserAction();
        $assignRoleToUser->handle($this->user, Role::Admin);

        $this->assertFalse($this->user->hasRole(Role::Customer));
    }

    public function test_assign_invalid_role_to_user()
    {
        $this->expectException(RoleNotFoundException::class);

        $assignRoleToUser = new AssignRoleToUserAction();
        $assignRoleToUser->handle($this->user, 'TestRole');
    }

    public function test_assign_permission_to_user()
    {
        # expected permission format from the font-end
        $permission = [
            Area::SuperAdmin . '-' . Subject::Customers => [
                Action::Index,
                Action::Create
            ]
        ];
        # assign the permissions to the user
        $assignPermissionToUser = new AssignPermissionToUserAction();
        $assignPermissionToUser->handle($this->user, $permission);

        $this->assertTrue($this->user->hasAnyDirectPermission(Grantify::transformSubjectActionToPermissionName($permission)));
    }

    public function test_assign_invalid_permission_to_user()
    {
        $this->expectException(Exception::class);

        # invalid permission format
        $permission = [
            Area::SuperAdmin . '-' . Action::Create . '.' . Subject::Customers
        ];
        $assignPermissionToUser = new AssignPermissionToUserAction();
        $assignPermissionToUser->handle($this->user, $permission);
    }

    public function test_sync_role_to_user()
    {
        $assignRoleToUser = new AssignRoleToUserAction();
        $assignRoleToUser->handle($this->user, Role::Admin);

        $syncRoleToUserAction = new SyncRoleToUserAction();
        $syncRoleToUserAction->handle($this->user, [Role::Admin, Role::Customer]);

        $this->assertTrue($this->user->hasExactRoles([Role::Customer, Role::Admin]));
    }

    public function test_sync_invalid_role_to_user()
    {
        $this->expectException(RoleDoesNotExist::class);

        $assignRoleToUser = new AssignRoleToUserAction();
        $assignRoleToUser->handle($this->user, Role::Customer);

        $syncRoleToUserAction = new SyncRoleToUserAction();
        $syncRoleToUserAction->handle($this->user, 'testRole');
    }

    /**
     * @throws Exception
     */
    public function test_sync_permission_to_user()
    {
        # expected permission format from the font-end
        $permissionAssign = [
            Area::SuperAdmin . '-' . Subject::Customers => [
                Action::Index,
                Action::Delete
            ]
        ];

        $permissionSync = [
            Area::SuperAdmin . '-' . Subject::Customers => [
                Action::Index,
                Action::Delete
            ],
            Area::SuperAdmin . '-' . Subject::Admins => [
                Action::Index,
                Action::Edit
            ]
        ];

        # assign the permissions to the user
        $assignPermissionToUser = new AssignPermissionToUserAction();
        $assignPermissionToUser->handle($this->user, $permissionAssign);

        $syncPermissionToUserAction = new SyncPermissionToUserAction();
        $syncPermissionToUserAction->handle($this->user, $permissionSync);

        $this->assertTrue($this->user->hasAllPermissions(Grantify::transformSubjectActionToPermissionName($permissionSync)));
    }

    public function test_sync_invalid_permission_to_user()
    {
        $this->expectException(Exception::class);

        # expected permission format from the font-end
        $permissionAssign = [
            Area::SuperAdmin . '-' . Subject::Customers => [
                Action::Index,
                Action::Delete
            ]
        ];

        # invalid permission
        $permissionSync = [
            Area::SuperAdmin . '-' . Action::Create => [
                Subject::Customers
            ],
            Area::SuperAdmin . '-' . Action::Delete => [
                Subject::Admins
            ]
        ];

        # assign the permissions to the user
        $assignPermissionToUser = new AssignPermissionToUserAction();
        $assignPermissionToUser->handle($this->user, $permissionAssign);

        $syncPermissionToUserAction = new SyncPermissionToUserAction();
        $syncPermissionToUserAction->handle($this->user, $permissionSync);
    }
}
