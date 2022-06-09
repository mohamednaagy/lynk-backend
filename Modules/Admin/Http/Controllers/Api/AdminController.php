<?php

namespace Modules\Admin\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
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
