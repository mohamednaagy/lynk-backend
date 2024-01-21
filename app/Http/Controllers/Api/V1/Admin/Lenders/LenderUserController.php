<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Companies\GetPaginatedLenderUsers;
use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\Lenders\UpdateLenderUserWithRoleAndPermission;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
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

class LenderUserController extends Controller
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

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Delete, Action::Manage])
        )->only('destroy');
    }

    public function index(
        Company $lender,
        GetPaginatedLenderUsers $getPaginatedLenderUsers
    ): JsonResponse {
        return fractal($getPaginatedLenderUsers->handle($lender), new UserTransformer(Area::Lender))
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
                'is_invitation_accepted',
            ])->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, Company $lender, User $user): JsonResponse
    {
        $this->ensureUserHasRoleInLenderAreaExceptApiUserRole($user);

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
     */
    public function store(
        StoreUserRequest $request,
        Company $lender,
        CreateLenderUserWithRoleAndPermission $createUserWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($lender, $request, $createUserWithRoleAndPermission) {
            $user = $createUserWithRoleAndPermission->handle(
                $request->validated() +
                    [
                        'company_id' => $lender->id,
                    ]
            );

            $invitationUrl = $request->validated('redirect_url');

            Mail::to($user)->send(new CompleteRegisterInvitation($user, $invitationUrl, CompanyType::Lender));

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
     */
    public function update(
        UpdateUserRequest $request,
        Company $lender,
        User $user,
        UpdateLenderUserWithRoleAndPermission $updateUserWithRoleAndPermission,
    ): JsonResponse {
        return DB::transaction((function () use ($request, $user, $updateUserWithRoleAndPermission) {
            $updateUserWithRoleAndPermission->handle($request->validated(), $user);

            return $this->successResponse();
        }));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $lender, User $user): JsonResponse
    {
        $this->ensureUserHasRoleInLenderAreaExceptApiUserRole($user);

        $user->update(['email' => $user->getEmailForSoftDeleting()]);
        $user->delete();

        return $this->successResponse();
    }

    public function ensureUserHasRoleInLenderAreaExceptApiUserRole(User $user)
    {
        if (! $user->hasAnyRole([
            Role::LenderAdmin,
            Role::LenderOrderCreator,
            Role::LenderBilling,
            Role::LenderSupervisor,
            Role::ApiAdmin,
        ])) {
            throw new AuthorizationException();
        }
    }
}
