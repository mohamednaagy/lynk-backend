<?php

namespace Modules\Admin\Http\Controllers\Api\Admins;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Actions\Admins\CreateAdmin;
use Modules\Admin\Actions\Admins\UpdateAdmin;
use Modules\Admin\Http\Requests\StoreUserRequest;
use Modules\Admin\Http\Requests\UpdateUserRequest;
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
     * @param StoreUserRequest $storeUserRequest
     * @param CreateAdmin $createAdmin
     * @return JsonResponse
     */
    public function store(StoreUserRequest $storeUserRequest, CreateAdmin $createAdmin): JsonResponse
    {
        return DB::transaction(function () use($storeUserRequest, $createAdmin) {
            $validated = $storeUserRequest->validated();
            $user = $createAdmin->handle($validated);

            return response()->jsonFormat([], 201);
        });
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateUserRequest $updateUserRequest
     * @param int $id
     * @param UpdateAdmin $updateAdmin
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $updateUserRequest, $id, UpdateAdmin $updateAdmin): JsonResponse
    {
        return DB::transaction(function () use($updateUserRequest, $id, $updateAdmin) {
            $admin = User::role(Role::Admin)->findOrFail($id);
            $validated = $updateUserRequest->validated();
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
