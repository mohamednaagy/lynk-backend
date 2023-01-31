<?php

namespace App\Http\Controllers\Api\V1\Admin\Traders;

use App\Actions\Contracts\Traders\CreateTraderUserWithRoleAndPermission;
use App\Actions\Contracts\Traders\GetPaginatedTraderUsers;
use App\Actions\Contracts\Traders\UpdateTraderUserWithRoleAndPermission;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Traders\Users\StoreUserRequest;
use App\Http\Requests\V1\Admin\Traders\Users\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TraderUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::TraderUsers, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::TraderUsers, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::TraderUsers, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::TraderUsers, Action::Edit, Action::Manage])
        )->only('update');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  GetPaginatedTraderUsers  $getPaginatedUsers
     * @return JsonResponse
     */
    public function index(GetPaginatedTraderUsers $getPaginatedUsers): JsonResponse
    {
        return fractal(
            $getPaginatedUsers->handle(),
            new UserTransformer(Area::Trader)
        )->parseIncludes([
            'id',
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'phone_country_code',
            'formatted_phone_number',
            'role',
            'is_active',
            'is_invitation_accepted',
        ])->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreUserRequest  $storeUserRequest
     * @param  Company  $trader
     * @param  CreateTraderUserWithRoleAndPermission  $createTraderUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreUserRequest $storeUserRequest,
        Company $trader,
        CreateTraderUserWithRoleAndPermission $createTraderUserWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($trader, $storeUserRequest, $createTraderUserWithRoleAndPermission) {
            $user = $createTraderUserWithRoleAndPermission->handle(
                $storeUserRequest->validated() +
                    [
                        'company_id' => $trader->id,
                    ]
            );

            return fractal($user, new UserTransformer())
                ->parseIncludes([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                    'is_active',
                    'is_invitation_accepted',
                ])->respond();
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  Company  $trader
     * @param  User  $user
     * @return JsonResponse
     */
    public function show(Company $trader, User $user): JsonResponse
    {
        $this->checkIfUserDoesNotHaveTraderAreaRole($user);

        $user->load('roles', 'permissions');

        return fractal($user, new UserTransformer(Area::Trader))
            ->parseIncludes([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
                'role',
                'is_active',
                'is_invitation_accepted',
            ])->respond();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateUserRequest  $updateUserRequest
     * @param  Company  $trader
     * @param  User  $user
     * @param  UpdateTraderUserWithRoleAndPermission  $updateTraderUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function update(
        UpdateUserRequest $updateUserRequest,
        Company $trader,
        User $user,
        UpdateTraderUserWithRoleAndPermission $updateTraderUserWithRoleAndPermission,
    ): JsonResponse {
        return DB::transaction((function () use ($updateUserRequest, $user, $updateTraderUserWithRoleAndPermission) {
            $this->checkIfUserDoesNotHaveTraderAreaRole($user);

            $updateTraderUserWithRoleAndPermission->handle($updateUserRequest->validated(), $user);

            return $this->successResponse();
        }));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  User  $user
     * @param  Company  $trader
     * @return JsonResponse
     */
    public function destroy(Company $trader, User $user): JsonResponse
    {
        $this->checkIfUserDoesNotHaveTraderAreaRole($user);
        $user->delete();

        return $this->successResponse();
    }

    public function checkIfUserDoesNotHaveTraderAreaRole(User $user)
    {
        if (! $user->hasRole(Area::roles(Area::Trader))) {
            throw new AuthorizationException();
        }
    }
}
