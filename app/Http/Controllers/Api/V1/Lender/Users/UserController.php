<?php

namespace App\Http\Controllers\Api\V1\Lender\Users;

use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\Lenders\GetPaginatedLenderUsers;
use App\Actions\Contracts\Lenders\UpdateLenderUserWithRoleAndPermission;
use App\Enums\Area;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Users\StoreUserRequest;
use App\Http\Requests\V1\Lender\Users\UpdateUserRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  GetPaginatedLenderUsers  $getPaginatedLenders
     * @return JsonResponse
     */
    public function index(GetPaginatedLenderUsers $getPaginatedLenders): JsonResponse
    {
        return fractal($getPaginatedLenders->handle(), new UserTransformer)
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
     * Store a newly created resource in storage.
     *
     * @param  StoreUserRequest  $storeUserRequest
     * @param  CreateLenderUserWithRoleAndPermission  $createLenderWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreUserRequest $storeUserRequest,
        CreateLenderUserWithRoleAndPermission $createLenderWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($storeUserRequest, $createLenderWithRoleAndPermission) {
            $user = $createLenderWithRoleAndPermission->handle($storeUserRequest->validated());

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
                    'role',
                ])->respond();
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  User  $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
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
     * Update the specified resource in storage.
     *
     * @param  User  $user
     * @param  UpdateUserRequest  $updateUserRequest
     * @param  UpdateLenderUserWithRoleAndPermission  $updateLenderUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function update(
        UpdateUserRequest $updateUserRequest,
        User $user,
        UpdateLenderUserWithRoleAndPermission $updateLenderUserWithRoleAndPermission,
    ): JsonResponse {
        return DB::transaction((function () use ($updateUserRequest, $user, $updateLenderUserWithRoleAndPermission) {
            if ($user->hasRole(Role::LenderApiUser) || $user->id == auth()->user()->getAuthIdentifier()) {
                throw new AuthorizationException();
            }

            $updateLenderUserWithRoleAndPermission->handle($updateUserRequest->validated(), $user);

            return $this->successResponse();
        }));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
    }
}
