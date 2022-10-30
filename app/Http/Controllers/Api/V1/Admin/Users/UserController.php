<?php

namespace App\Http\Controllers\Api\V1\Admin\Users;

use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\Lenders\UpdateLenderUserWithRoleAndPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\Users\UpdateUserRequest;
use App\Http\Requests\V1\Lender\Users\StoreCompanyUserRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreCompanyUserRequest  $storeCompanyUserRequest
     * @param  CreateLenderUserWithRoleAndPermission  $createUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreCompanyUserRequest $storeCompanyUserRequest,
        CreateLenderUserWithRoleAndPermission $createUserWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($storeCompanyUserRequest, $createUserWithRoleAndPermission) {
            $user = $createUserWithRoleAndPermission->handle($storeCompanyUserRequest->validated());
            $invitationUrl = $storeCompanyUserRequest->validated('redirect_url');
            Mail::to($user->email)->send(new CompleteRegisterInvitation($user, $invitationUrl));

            return fractal($user, new UserTransformer())->respond();
        });
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateUserRequest  $updateUserRequest
     * @param  User  $user
     * @param  UpdateLenderUserWithRoleAndPermission  $updateUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function update(
        UpdateUserRequest $updateUserRequest,
        User $user,
        UpdateLenderUserWithRoleAndPermission $updateUserWithRoleAndPermission,
    ): JsonResponse {
        return DB::transaction((function () use ($updateUserRequest, $user, $updateUserWithRoleAndPermission) {
            $updateUserWithRoleAndPermission->handle($updateUserRequest->validated(), $user);

            return $this->successResponse();
        }));
    }
}
