<?php

namespace Modules\Admin\Http\Controllers\Api\V1\Admins;

use Exception;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Permission\Enums\Role;
use App\Http\Controllers\Controller;
use Modules\Admin\Http\Resources\AuthResource;
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
    public function index() : ResourceCollection
    {
       $users = User::role(Role::Admin)->paginate();

       return  AuthResource::collection($users);
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
        UpdateAdminWithRoleAndPermission $updateAdminWithRoleAndPermission
    ): JsonResponse
    {
        try {
            return DB::transaction(function () use($updateAdminRequest, $id, $updateAdminWithRoleAndPermission) {
                $admin = User::role(Role::Admin)->findOrFail($id);
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
    public function destroy($id) : JsonResponse
   {
        $user = User::role(Role::Admin)->findOrFail($id);

       $user->delete();
       return  response()->jsonFormat([]);
   }

}
