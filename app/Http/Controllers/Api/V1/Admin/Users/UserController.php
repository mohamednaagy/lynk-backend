<?php

namespace App\Http\Controllers\Api\V1\Admin\Users;

use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Users\StoreCompanyUserRequest;
use App\Mail\CompleteRegisterInvitation;
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
}
