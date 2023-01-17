<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\GetPaginatedCompanyUsers;
use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\Lenders\UpdateLenderUserWithRoleAndPermission;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\GetCompanyUsersRequest;
use App\Http\Requests\V1\Admin\Companies\Users\StoreUserRequest;
use App\Http\Requests\V1\Admin\Companies\Users\UpdateUserRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CompanyUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Edit, Action::Manage])
        )->only('update');
    }

    public function index(
        GetCompanyUsersRequest $request,
        Company $company,
        GetPaginatedCompanyUsers $getPaginatedCompanyUsers
    ): JsonResponse {
        return fractal($getPaginatedCompanyUsers->handle($company), new UserTransformer(Area::Lender))
            ->parseIncludes([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
                'orders_count',
                'role',
            ])->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @param  Company  $company
     * @param  User  $user
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, Company $company, User $user): JsonResponse
    {
        if (! $user->hasAnyRole([
            Role::LenderAdmin,
            Role::LenderOrderCreator,
            Role::LenderBilling,
            Role::LenderSupervisor,
        ])) {
            throw new AuthorizationException();
        }

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
     * @param  StoreUserRequest  $storeUserRequest
     * @param  Company  $company
     * @param  CreateLenderUserWithRoleAndPermission  $createUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreUserRequest $storeUserRequest,
        Company $company,
        CreateLenderUserWithRoleAndPermission $createUserWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($company, $storeUserRequest, $createUserWithRoleAndPermission) {
            $user = $createUserWithRoleAndPermission->handle(
                $storeUserRequest->validated() +
                [
                    'company_id' => $company->id,
                ]
            );

            $invitationUrl = $storeUserRequest->validated('redirect_url');

            Mail::to($user)->send(new CompleteRegisterInvitation($user, $invitationUrl));

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
     * @param  Company  $company
     * @param  User  $user
     * @param  UpdateLenderUserWithRoleAndPermission  $updateUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function update(
        UpdateUserRequest $updateUserRequest,
        Company $company,
        User $user,
        UpdateLenderUserWithRoleAndPermission $updateUserWithRoleAndPermission,
    ): JsonResponse {
        return DB::transaction((function () use ($updateUserRequest, $user, $updateUserWithRoleAndPermission) {
            $updateUserWithRoleAndPermission->handle($updateUserRequest->validated(), $user);

            return $this->successResponse();
        }));
    }
}
