<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Contracts\CreateAdminWithRoleAndPermission;
use App\Actions\Contracts\FindUserByIdAndRole;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\StoreAdminRequest;
use App\Http\Requests\V1\Admin\UpdateAdminRequest;
use App\Http\Resources\AuthResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * @param  GetPaginatedUsersByRole  $getPaginatedUsersByRole
     * @return ResourceCollection
     */
    public function index(GetPaginatedUsersByRole $getPaginatedUsersByRole): ResourceCollection
    {
        $admins = $getPaginatedUsersByRole->handle(Role::Admin);

        return  AuthResource::collection($admins);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreAdminRequest  $storeAdminRequest
     * @param  CreateAdminWithRoleAndPermission  $createAdminWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreAdminRequest $storeAdminRequest,
        CreateAdminWithRoleAndPermission $createAdminWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($storeAdminRequest, $createAdminWithRoleAndPermission) {
            $createAdminWithRoleAndPermission->handle($storeAdminRequest->validated());

            return $this->successResponse();
        });
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
