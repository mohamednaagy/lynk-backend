<?php

namespace Modules\Admin\Http\Controllers\Api\V1\Admins;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Permission\Enums\Role;
use App\Http\Controllers\Controller;
use Modules\Admin\Http\Resources\AuthResource;
use App\Actions\Contracts\FindUserByIdAndRole;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Admin\Http\Requests\Admins\StoreAdminRequest;
use Modules\Admin\Http\Requests\Admins\UpdateAdminRequest;
use Modules\Admin\Actions\Contracts\CreateAdminWithRoleAndPermission;
use Modules\Admin\Actions\Contracts\UpdateAdminWithRoleAndPermission;

class AdminController extends Controller
{
    /**
     * @return ResourceCollection
     */
    public function index(GetPaginatedUsersByRole $getPaginatedUsersByRole): ResourceCollection
    {
       $admins = $getPaginatedUsersByRole(Role::Admin);

       return  AuthResource::collection($admins);
   }

    /**
     * Store a newly created resource in storage.
     * @param StoreAdminRequest $storeAdminRequest
     * @param CreateAdminWithRoleAndPermission $createAdminWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreAdminRequest $storeAdminRequest,
        CreateAdminWithRoleAndPermission $createAdminWithRoleAndPermission
    ): JsonResponse
    {
        try {
            return DB::transaction(function () use ($storeAdminRequest, $createAdminWithRoleAndPermission) {
                $createAdminWithRoleAndPermission($storeAdminRequest->validated());
                return $this->successResponse();
            });
        }catch (Exception $exception){
            return $this->errorResponse(message: $exception->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateAdminRequest $updateAdminRequest
     * @param int $id
     * @param UpdateAdminWithRoleAndPermission $updateAdminWithRoleAndPermission
     * @return JsonResponse
     */
    public function update(
        UpdateAdminRequest $updateAdminRequest,
        int $id,
        UpdateAdminWithRoleAndPermission $updateAdminWithRoleAndPermission,
        FindUserByIdAndRole $findUserByIdAndRole
    ): JsonResponse
    {
        try {
            return DB::transaction(function () use($updateAdminRequest, $id, $updateAdminWithRoleAndPermission, $findUserByIdAndRole) {
                $admin = $findUserByIdAndRole($id, Role::Admin);
                if (!$admin)
                    return $this->errorResponse();

                $updateAdminWithRoleAndPermission($updateAdminRequest->validated(), $admin);
                return $this->successResponse();
            });
        }catch (Exception $exception){
            return $this->errorResponse(message: $exception->getMessage());
        }
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id, FindUserByIdAndRole $findUserByIdAndRole): JsonResponse
   {
       $admin = $findUserByIdAndRole($id, Role::Admin);
       if (!$admin)
           return $this->errorResponse();

       $admin->delete();

       return $this->successResponse();
   }

}
