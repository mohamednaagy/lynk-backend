<?php

namespace Tests\Feature\Endpoints\V1\Grantify;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;

class GrantifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_roles_throw_exception_for_unauthorized_user(): void
    {
        $token = $this->login(Role::LenderAdmin);
        $authorizationToken = $this->createUserAuthorizationToken();
        // get auth user data
        $getOtpCodeResponse = $this->withToken($token)->getJson('api/v1/admin/roles', [
            'authorized_token' => $authorizationToken,
        ]);
        $getOtpCodeResponse->assertStatus(403)->assertJsonStructure([
            'message',
        ]);
    }

    public function test_get_roles(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        // get object of the user model and assign permission to it
        $user = User::find(auth()->id());
//        $permission = Area::SuperAdmin.'-'.Subject::Admins.'.'.Action::Index;
//        Grantify::assignPermissionToModel($user, $permission);

        // get auth user data
        $getOtpCodeResponse = $this->withToken($token)->getJson('api/v1/admin/roles', [
            'authorized_token' => $authorizationToken,
        ]);
        $getOtpCodeResponse->assertStatus(200)->assertJsonStructure([
            'data',
        ]);
    }

    public function test_get_permissions_for_unauthorized_user(): void
    {
        $token = $this->login(Role::LenderAdmin);
        $authorizationToken = $this->createUserAuthorizationToken();
        // get auth user data
        $getOtpCodeResponse = $this->withToken($token)->getJson('api/v1/admin/roles', [
            'authorized_token' => $authorizationToken,
        ]);
        $getOtpCodeResponse->assertStatus(403)->assertJsonStructure([
            'message',
        ]);
    }

    public function test_get_permissions(): void
    {
        $token = $this->login();
        $authorizationToken = $this->createUserAuthorizationToken();

        // get object of the user model and assign permission to it
        $user = User::find(auth()->id());
        $permission = Area::SuperAdmin.'-'.Subject::Admins.'.'.Action::Index;
        Grantify::assignPermissionToModel($user, $permission);

        // get auth user data
        $getOtpCodeResponse = $this->withToken($token)->getJson('api/v1/admin/permissions', [
            'authorized_token' => $authorizationToken,
        ]);
        $getOtpCodeResponse->assertStatus(200)->assertJsonStructure([
            'data',
        ]);
    }
}
