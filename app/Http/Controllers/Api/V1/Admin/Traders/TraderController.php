<?php

namespace App\Http\Controllers\Api\V1\Admin\Traders;

use App\Actions\Contracts\Traders\CreateTraderUserWithRoleAndPermission;
use App\Actions\Contracts\Traders\UpdateTraderUserWithRoleAndPermission;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Users\StoreUserRequest;
use App\Http\Requests\V1\Trader\Users\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TraderController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::TraderUsers, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::TraderUsers, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::TraderUsers, Action::Edit, Action::Manage])
        )->only('update');
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
        if (! $user->hasRole(Role::TraderAdmin)) {
            throw new ModelNotFoundException();
        }

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
            if (! $user->hasRole(Role::TraderAdmin) || $user->id == auth()->id()) {
                throw new AuthorizationException();
            }

            $updateTraderUserWithRoleAndPermission->handle($updateUserRequest->validated(), $user);

            return $this->successResponse();
        }));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return void
     */
    public function destroy($id)
    {
        //
    }
}
