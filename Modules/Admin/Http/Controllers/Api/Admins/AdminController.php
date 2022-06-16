<?php

namespace Modules\Admin\Http\Controllers\Api\Admins;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Actions\Admins\CreateAdmin;
use Modules\Admin\Actions\Admins\UpdateAdmin;
use Modules\Admin\Http\Requests\Admins\StoreAdminRequest;
use Modules\Admin\Http\Requests\Admins\UpdateAdminRequest;
use Modules\Admin\Http\Resources\AuthResource;
use Modules\Permission\Enums\Role;
use function response;

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
     * @param CreateAdmin $createAdmin
     * @return JsonResponse
     */
    public function store(StoreAdminRequest $storeAdminRequest, CreateAdmin $createAdmin): JsonResponse
    {
        return DB::transaction(function () use($storeAdminRequest, $createAdmin) {
            $validated = $storeAdminRequest->validated();
            $user = $createAdmin->handle($validated);

            return response()->jsonFormat([], 201);
        });
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateAdminRequest $updateAdminRequest
     * @param int $id
     * @param UpdateAdmin $updateAdmin
     * @return JsonResponse
     */
    public function update(UpdateAdminRequest $updateAdminRequest, $id, UpdateAdmin $updateAdmin): JsonResponse
    {
        return DB::transaction(function () use($updateAdminRequest, $id, $updateAdmin) {
            $admin = User::role(Role::Admin)->findOrFail($id);
            $validated = $updateAdminRequest->validated();
            $updateAdmin->handle($validated, $admin);

            return response()->jsonFormat([]);
        });
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
