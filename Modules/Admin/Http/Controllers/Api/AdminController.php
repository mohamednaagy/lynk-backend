<?php

namespace Modules\Admin\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Admin\Actions\Api\CreateNewUser;
use Modules\Admin\Actions\Api\UpdateUser;
use Modules\Admin\Http\Requests\CreateUserRequest;
use Modules\Admin\Http\Requests\UpdateUserRequest;
use Modules\Admin\Http\Resources\AuthResource;

class AdminController extends Controller
{

    /**
     * @return ResourceCollection
     */
    public function index() : ResourceCollection
    {
       $users = User::role('Admin')->get();

       return  AuthResource::collection($users);
   }

    /**
     * Store a newly created resource in storage.
     * @param CreateUserRequest $createUserRequest
     * @param CreateNewUser $createNewUser
     * @return JsonResponse
     */
    public function store(CreateUserRequest $createUserRequest, CreateNewUser $createNewUser): JsonResponse
    {
        $createNewUser->handle($createUserRequest);
        return response()->jsonFormat([ 'message' => trans('admin::response.admin.created')], 201);
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateUserRequest $updateUserRequest
     * @param int $id
     * @param UpdateUser $updateUser
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $updateUserRequest, $id, UpdateUser $updateUser): JsonResponse
    {
        $admin = User::role('Admin')->find($id);

        if (!$admin)
            return response()->jsonFormat(['message' => trans('admin::response.admin.not_found')], 404);

        $updateUser->handle($updateUserRequest, $admin);

        return response()->jsonFormat([ 'message' => trans('admin::response.admin.updated')]);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id) : JsonResponse
   {
        $user = User::role('Admin')->find($id);

        if (!$user)
            return  response()->jsonFormat( ['message' => trans('admin::response.admin.not_found') ],404);

       $user->delete();
       return  response()->jsonFormat(['message' => trans('admin::response.admin.deleted') ]);
   }

}
