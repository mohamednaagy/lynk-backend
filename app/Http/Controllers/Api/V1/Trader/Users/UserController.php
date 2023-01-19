<?php

namespace App\Http\Controllers\Api\V1\Trader\Users;

use App\Actions\Contracts\Traders\CreateTraderUserWithRoleAndPermission;
use App\Actions\Contracts\Traders\GetPaginatedTraderUsers;
use App\Actions\Contracts\Traders\UpdateTraderUserWithRoleAndPermission;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Users\StoreUserRequest;
use App\Http\Requests\V1\Trader\Users\UpdateUserRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::TraderUsers, Action::Index, Action::Manage]).'|'.
            perm(Area::Trader, [Subject::All, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::TraderUsers, Action::Create, Action::Manage]).'|'.
            perm(Area::Trader, [Subject::All, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::TraderUsers, Action::Show, Action::Manage]).'|'.
            perm(Area::Trader, [Subject::All, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::TraderUsers, Action::Edit, Action::Manage]).'|'.
            perm(Area::Trader, [Subject::All, Action::Manage])
        )->only('update');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  GetPaginatedTraderUsers  $getPaginatedTraderUsers
     * @return JsonResponse
     */
    public function index(GetPaginatedTraderUsers $getPaginatedTraderUsers): JsonResponse
    {
        return fractal($getPaginatedTraderUsers->handle(), new UserTransformer(Area::Trader))
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
     * @param  StoreUserRequest  $request
     * @param  CreateTraderUserWithRoleAndPermission  $createTraderUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreUserRequest $request,
        CreateTraderUserWithRoleAndPermission $createTraderUserWithRoleAndPermission
    ): JsonResponse {
        return DB::transaction(function () use ($request, $createTraderUserWithRoleAndPermission) {
            $user = $createTraderUserWithRoleAndPermission->handle($request->validated());

            $invitationUrl = $request->validated('redirect_url');
            Mail::to($user)->send(new CompleteRegisterInvitation($user, $invitationUrl));

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
        $user->load('roles', 'permissions');

        return fractal($user, new UserTransformer(Area::Trader))
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
     * @param  UpdateUserRequest  $request
     * @param  User  $user
     * @param  UpdateTraderUserWithRoleAndPermission  $updateTraderUserWithRoleAndPermission
     * @return JsonResponse
     */
    public function update(
        UpdateUserRequest $request,
        User $user,
        UpdateTraderUserWithRoleAndPermission $updateTraderUserWithRoleAndPermission,
    ): JsonResponse {
        return DB::transaction((function () use ($request, $user, $updateTraderUserWithRoleAndPermission) {
            if ($user->id == auth()->user()->getAuthIdentifier()) {
                throw new AuthorizationException();
            }

            $updateTraderUserWithRoleAndPermission->handle($request->validated(), $user);

            return $this->successResponse();
        }));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
