<?php

namespace App\Http\Controllers\Api\V1\Supplier\Users;

use App\Actions\Contracts\Commodities\CommoditySupplier\CreateSupplierUserWithRoleAndPermission;
use App\Actions\Contracts\Commodities\CommoditySupplier\GetPaginatedSupplierUsers;
use App\Actions\Contracts\Commodities\CommoditySupplier\UpdateSupplierUserWithRoleAndPermission;
use App\Http\Controllers\Controller;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Requests\V1\Supplier\CommoditySupplier\Users\StoreUserRequest;
use App\Http\Requests\V1\Supplier\CommoditySupplier\Users\UpdateUserRequest;
use App\Mail\Supplier\CompleteSupplierRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:' .
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:' .
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Manage, Action::Create])
        )->only('store');

        $this->middleware(
            'permission:' .
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Edit, Action::Manage])
        )->only('update');
    }

    /**
     * Display a paginated list of supplier users.
     *
     * @param  GetPaginatedSupplierUsers  $getPaginatedUsers
     * @return JsonResponse
     */
    public function index(GetPaginatedSupplierUsers $getPaginatedUsers): JsonResponse
    {
        return fractal(
            $getPaginatedUsers->handle(tenant()),
            new UserTransformer(Area::CommoditySupplier)
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
     * Store a newly created supplier user in storage.
     *
     * @param  StoreUserRequest  $request
     * @param  CreateSupplierUserWithRoleAndPermission  $createSupplierUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request, CreateSupplierUserWithRoleAndPermission $createSupplierUserWithRoleAndPermission): JsonResponse
    {
        return DB::transaction(function () use ($request, $createSupplierUserWithRoleAndPermission) {
            $user = $createSupplierUserWithRoleAndPermission->handle(
                $request->validated() +
                [
                    'company_id' => tenant()->id,
                ]
            );
            $invitationUrl = $request->validated('redirect_url');
            Mail::to($user)->send(new CompleteSupplierRegisterInvitation($user, $invitationUrl));

            return fractal($user, new UserTransformer)
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
     * Update the specified supplier user in storage.
     *
     * @param  UpdateUserRequest  $updateUserRequest
     * @param  User  $user
     * @param  UpdateSupplierUserWithRoleAndPermission  $updateSupplierUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function update(
        UpdateUserRequest $updateUserRequest,
        User $user,
        UpdateSupplierUserWithRoleAndPermission $updateSupplierUserWithRoleAndPermission,
    ): JsonResponse {

        return DB::transaction((function () use ($updateUserRequest, $user, $updateSupplierUserWithRoleAndPermission) {
            $this->checkIfUserDoesNotHaveSupplierAreaRole($user);
            $updateSupplierUserWithRoleAndPermission->handle($updateUserRequest->validated(), $user);

            return $this->successResponse();
        }));
    }

    public function checkIfUserDoesNotHaveSupplierAreaRole(User $user)
    {
        if (!$user->hasRole(Area::roles(Area::CommoditySupplier))) {
            throw new AuthorizationException();
        }
    }


    /**
     * Display the specified supplier user resource.
     *
     * @param  User  $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        $this->checkIfUserDoesNotHaveSupplierAreaRole($user);

        $user->load('roles', 'permissions');

        return fractal($user, new UserTransformer(Area::CommoditySupplier))
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

}
