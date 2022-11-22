<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\GetCompanyUsers;
use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\Lenders\UpdateLenderUserWithRoleAndPermission;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\GetCompanyUsersRequest;
use App\Http\Requests\V1\Admin\Companies\Users\UpdateUserRequest;
use App\Http\Requests\V1\Lender\Users\StoreCompanyUserRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index(
        GetCompanyUsersRequest $getCompanyUsersRequest,
        Company $company,
        // __REVIEW__ change to GetPaginatedCompanyUsers $getPaginatedCompanyUsers
        GetCompanyUsers $getCompanyUsers
    ): JsonResponse {
        return fractal($getCompanyUsers->handle($company), new UserTransformer)
            ->parseIncludes([
                // __REVIEW__ add number of orders created by each
                'id',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
            ])->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @param  User  $user
     * @return JsonResponse
     */
    public function show(Request $request, User $user): JsonResponse
    {
        // __REVIEW__ check if the user has one of the following roles:
        // Role::LenderAdmin,
        // Role::LenderOrderCreator,
        // Role::LenderBilling,
        // Role::LenderSupervisor,
        return fractal($user, new UserTransformer(Area::Lender))
            ->parseIncludes([
                'id',
                'first_name',
                'last_name',
                'email',
                'role',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
            ])->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreCompanyUserRequest  $storeCompanyUserRequest
     * @param  Company  $company
     * @param  CreateLenderUserWithRoleAndPermission  $createUserWithRoleAndPermission
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function store(
        StoreCompanyUserRequest $storeCompanyUserRequest,
        Company $company,
        CreateLenderUserWithRoleAndPermission $createUserWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($company, $storeCompanyUserRequest, $createUserWithRoleAndPermission) {
            // __REVIEW__ leave some spaces between lines of unrelated functions
            // Example, between $invitationUrl... and create user, add new line
            $user = $createUserWithRoleAndPermission->handle(
                $storeCompanyUserRequest->validated() +
                    [
                        'company_id' => $company->id,
                    ]
            );
            $invitationUrl = $storeCompanyUserRequest->validated('redirect_url');
            // __REVIEW__ pass $user not email to the "->to(...)"
            Mail::to($user->email)->send(new CompleteRegisterInvitation($user, $invitationUrl));

            return fractal($user, new UserTransformer())
                ->parseIncludes([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                ])->respond();
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
