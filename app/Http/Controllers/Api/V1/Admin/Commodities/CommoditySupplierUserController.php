<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommoditySupplier\CreateSupplierUserWithRoleAndPermission;
use App\Actions\Contracts\Commodities\CommoditySupplier\GetPaginatedSupplierUsers;
use App\Actions\Contracts\Commodities\CommoditySupplier\UpdateSupplierUserWithRoleAndPermission;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\Users\StoreUserRequest;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\Users\UpdateUserRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CommoditySupplierUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierUsers, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierUsers, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierUsers, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierUsers, Action::Edit, Action::Manage])
        )->only('update');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierUsers, Action::Delete, Action::Manage])
        )->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Supplier $supplier,
        GetPaginatedSupplierUsers $getPaginatedUsers,
    ): JsonResponse {
        return fractal(
            $getPaginatedUsers->handle($supplier),
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
     * Store a newly created resource in storage.
     *
     * @param  StoreUserRequest  $storeUserRequest
     */
    public function store(
        StoreUserRequest $request,
        Supplier $supplier,
        CreateSupplierUserWithRoleAndPermission $createSupplierUserWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($supplier, $request, $createSupplierUserWithRoleAndPermission) {
            $user = $createSupplierUserWithRoleAndPermission->handle(
                $request->validated() +
                [
                    'company_id' => $supplier->id,
                ]
            );
            $invitationUrl = $request->validated('redirect_url');
            Mail::to($user)->send(new CompleteRegisterInvitation($user, $invitationUrl, CompanyType::Supplier));

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
     */
    public function show(Company $supplier, User $user): JsonResponse
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
     * Update the specified resource in storage.
     *
     * @param  UpdateUserRequest  $updateUserRequest
     * @param  Company  $supplier
     * @param  UpdateSupplierUserWithRoleAndPermission  $updateSupplierUserWithRoleAndPermission
     * @return JsonResponse
     */
    // public function update(
    //     UpdateUserRequest $updateUserRequest,
    //     Company $supplier,
    //     User $user,
    //     UpdateSupplierUserWithRoleAndPermission $updateSupplierUserWithRoleAndPermission,
    // ): JsonResponse {
    //     return DB::transaction((function () use ($updateUserRequest, $user, $updateSupplierUserWithRoleAndPermission) {
    //         $this->checkIfUserDoesNotHaveSupplierAreaRole($user);

    //         $updateSupplierUserWithRoleAndPermission->handle($updateUserRequest->validated(), $user);

    //         return $this->successResponse();
    //     }));
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  *
    //  * @param  User  $user
    //  * @param  CommoditySupplier  $supplier
    //  * @return JsonResponse
    //  */
    // public function destroy(Company $supplier, User $user): JsonResponse
    // {
    //     $this->checkIfUserDoesNotHaveSupplierAreaRole($user);

    //     $user->update(['email' => $user->getEmailForSoftDeleting()]);
    //     $user->delete();

    //     return $this->successResponse();
    // }

    public function checkIfUserDoesNotHaveSupplierAreaRole(User $user)
    {
        if (! $user->hasRole(Area::roles(Area::CommoditySupplier))) {
            throw new AuthorizationException();
        }
    }
}
