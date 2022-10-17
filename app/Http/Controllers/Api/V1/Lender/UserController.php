<?php

namespace App\Http\Controllers\Api\v1\Lender\Users;

use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Users\StoreUserRequest;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(
        StoreUserRequest $storeUserRequest,
        CreateLenderUserWithRoleAndPermission $createLenderWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($storeUserRequest, $createLenderWithRoleAndPermission) {
            $user = $createLenderWithRoleAndPermission->handle($storeUserRequest->validated());

            return fractal($user, new UserTransformer())->respond();
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
