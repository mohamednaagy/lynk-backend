<?php

namespace App\Http\Controllers\Api\v1\Lender\Users;

use App\Actions\Contracts\Lenders\CreateLenderWithRoleAndPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lender\Users\StoreUserRequest;
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
        StoreUserRequest $StoreUserRequest,
        CreateLenderWithRoleAndPermission $CreateLenderWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($StoreUserRequest, $CreateLenderWithRoleAndPermission) {
            $CreateLenderWithRoleAndPermission->handle($StoreUserRequest->validated());

            return $this->successResponse();
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
