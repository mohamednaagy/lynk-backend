<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Contracts\CreateAdminWithRoleAndPermission;
use App\Actions\Contracts\FindUserByIdAndRole;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Enums\Area;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\StoreAdminRequest;
use App\Http\Requests\V1\Admin\UpdateAdminRequest;
use App\Http\Resources\AuthResource;
use App\Mail\Admin\CompleteAdminRegisterInvitation;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Grantify\Facades\Grantify;

class AdminController extends Controller
{
    /**
     * @param  GetPaginatedUsersByRole  $getPaginatedUsersByRole
     * @return ResourceCollection
     */
    public function index(GetPaginatedUsersByRole $getPaginatedUsersByRole): ResourceCollection
    {
        $admins = $getPaginatedUsersByRole->handle(Role::Admin);

        return AuthResource::collection($admins);
    }

    /**
     * @param $id
     * @param  FindUserByIdAndRole  $findUserByIdAndRole
     * @return JsonResponse
     */
    public function show(
        $id,
        FindUserByIdAndRole $findUserByIdAndRole
    ): JsonResponse {
        $admin = $findUserByIdAndRole->handle($id, Role::Admin);
        if (! $admin) {
            return $this->errorResponse();
        }

        return fractal($admin, new UserTransformer())
            ->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreAdminRequest  $createAdminRequest
     * @param  CreateAdminWithRoleAndPermission  $createAdminWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreAdminRequest $createAdminRequest,
        CreateAdminWithRoleAndPermission $createAdminWithRoleAndPermission
    ): JsonResponse {
        $data = $createAdminRequest->validated();
        $data['role'] = Role::Admin;
        $data['permissions'] = Grantify::transformToAreaSubject(Area::SuperAdmin, $data['permissions']);

        DB::transaction(function () use ($data, $createAdminWithRoleAndPermission) {
            $admin = $createAdminWithRoleAndPermission->handle($data);

            Mail::to($admin->email)->send(new CompleteAdminRegisterInvitation($admin, $data['redirect_url']));
        });

        return $this->successResponse();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateAdminRequest  $updateAdminRequest
     * @param  int  $id
     * @param  UpdateAdminWithRoleAndPermission  $updateAdminWithRoleAndPermission
     * @param  FindUserByIdAndRole  $findUserByIdAndRole
     * @return JsonResponse
     */
    public function update(
        UpdateAdminRequest $updateAdminRequest,
        int $id,
        UpdateAdminWithRoleAndPermission $updateAdminWithRoleAndPermission,
        FindUserByIdAndRole $findUserByIdAndRole
    ): JsonResponse {
        return DB::transaction(function () use ($updateAdminRequest, $id, $updateAdminWithRoleAndPermission, $findUserByIdAndRole) {
            $admin = $findUserByIdAndRole->handle($id, Role::Admin);
            if (! $admin) {
                return $this->errorResponse();
            }

            $updateAdminWithRoleAndPermission->handle($updateAdminRequest->validated(), $admin);

            return $this->successResponse();
        });
    }

    /**
     * @param  int  $id
     * @param  FindUserByIdAndRole  $findUserByIdAndRole
     * @return JsonResponse
     */
    public function destroy(int $id, FindUserByIdAndRole $findUserByIdAndRole): JsonResponse
    {
        $admin = $findUserByIdAndRole->handle($id, Role::Admin);
        if (! $admin) {
            return $this->errorResponse();
        }

        $admin->delete();

        return $this->successResponse();
    }
}
