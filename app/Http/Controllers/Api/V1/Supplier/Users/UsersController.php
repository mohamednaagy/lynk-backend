<?php

namespace App\Http\Controllers\Api\V1\Supplier\Users;

use App\Actions\Contracts\Commodities\CommoditySupplier\CreateSupplierUserWithRoleAndPermission;
use App\Actions\Contracts\Commodities\CommoditySupplier\GetPaginatedSupplierUsers;
use App\Actions\Contracts\Commodities\CommoditySupplier\UpdateSupplierUserWithRoleAndPermission;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
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
            'permission:'.
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Manage, Action::Create])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Edit, Action::Manage])
        )->only('update');

        $this->middleware(
            'permission:'.
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Delete, Action::Manage])
        )->only('destroy');
    }

    /**
     * Display a paginated list of supplier users.
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
        if (! $user->hasRole(Area::roles(Area::CommoditySupplier))) {
            throw new AuthorizationException;
        }
    }

    /**
     * Display the specified supplier user resource.
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

    /**
     * Soft deletes the specified supplier user.
     *
     * This method updates the user's email to a format suitable for soft deletion
     * and then deletes the user. It ensures the user has the necessary supplier
     * area role before proceeding with the deletion.
     *
     * @param  User  $user  The user to be soft deleted.
     * @return JsonResponse A success response upon successful deletion.
     *
     * @throws AuthorizationException If the user does not have the required role.
     */
    public function destroy(User $user)
    {
        if ($user->id == auth()->user()->id) {
            throw new AuthorizationException(__('You cannot delete yourself'));
        }

        $this->checkIfUserDoesNotHaveSupplierAreaRole($user);

        $user->update(['email' => $user->getEmailForSoftDeleting()]);
        $user->delete();

        return $this->successResponse();
    }
}
