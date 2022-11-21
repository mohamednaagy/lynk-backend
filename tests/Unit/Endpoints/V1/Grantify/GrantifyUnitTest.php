<?php

namespace Tests\Unit\Endpoints\V1\Grantify;

use App\Actions\AssignPermissionToUserAction;
use App\Actions\AssignRoleToUserAction;
use App\Actions\SyncPermissionToUserAction;
use App\Actions\SyncRoleToUserAction;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Exceptions\RoleNotFoundException;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Tests\TestCase;

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
        // expected permission format from the font-end
        $permission = [
            [
                'subject' => Area::SuperAdmin.'-'.Subject::Admins,
                'actions' => [
                    Action::Index,
                    Action::Create,
                ],
            ],
        ];
        // assign the permissions to the user
        $assignPermissionToUser = new AssignPermissionToUserAction();
        $assignPermissionToUser->handle($this->user, $permission);

        $this->assertTrue($this->user->hasAnyDirectPermission(Grantify::transformSubjectActionToPermissionName($permission)));
    }

    public function test_assign_invalid_permission_to_user()
    {
        $this->expectException(Exception::class);

        // invalid permission format
        $permission = [
            0 => [
                'subject' => Area::SuperAdmin.'-'.Action::Create.'.'.Subject::Admins,
            ],
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
        // expected permission format from the font-end
        $permissionAssign = [
            0 => [
                'subject' => Area::SuperAdmin.'-'.Subject::Admins,
                'actions' => [
                    Action::Index,
                    Action::Delete,
                ],
            ],
        ];

        $permissionSync = [
            0 => [
                'subject' => Area::SuperAdmin.'-'.Subject::Dashboard,
                'actions' => [
                    Action::Show,
                ],
            ],
            1 => [
                'subject' => Area::SuperAdmin.'-'.Subject::Admins,
                'actions' => [
                    Action::Index,
                    Action::Delete,
                ],
            ],
        ];

        // assign the permissions to the user
        $assignPermissionToUser = new AssignPermissionToUserAction();
        $assignPermissionToUser->handle($this->user, $permissionAssign);

        $syncPermissionToUserAction = new SyncPermissionToUserAction();
        $syncPermissionToUserAction->handle($this->user, $permissionSync);

        $this->assertTrue($this->user->hasAllPermissions(Grantify::transformSubjectActionToPermissionName($permissionSync)));
    }

    public function test_sync_invalid_permission_to_user()
    {
        $this->expectException(Exception::class);

        // expected permission format from the font-end
        $permissionAssign = [
            Area::SuperAdmin.'-'.Subject::Admins => [
                Action::Index,
                Action::Delete,
            ],
        ];

        // invalid permission
        $permissionSync = [
            Area::SuperAdmin.'-'.Action::Create => [
                Subject::Admins,
            ],
            Area::SuperAdmin.'-'.Action::Delete => [
                Subject::Admins,
            ],
        ];

        // assign the permissions to the user
        $assignPermissionToUser = new AssignPermissionToUserAction();
        $assignPermissionToUser->handle($this->user, $permissionAssign);

        $syncPermissionToUserAction = new SyncPermissionToUserAction();
        $syncPermissionToUserAction->handle($this->user, $permissionSync);
    }
}
