<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Actions\Contracts\CreateAdminWithRoleAndPermission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Auth\StoreAdminRequest;
use App\Mail\Admin\CompleteAdminRegisterInvitation;
use Illuminate\Support\Facades\Mail;

class CreateAdmin extends Controller
{
    public function __invoke(
        StoreAdminRequest $createAdminRequest,
        CreateAdminWithRoleAndPermission $createAdminWithRoleAndPermission
    ) {
        $data = $createAdminRequest->validated();
        $data['role'] = Role::Admin;

        $admin = $createAdminWithRoleAndPermission->handle($data);

        Mail::to($admin->email)->send(new CompleteAdminRegisterInvitation($admin, $data['redirect_url']));

        return $this->successResponse();
    }
}
