<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Contracts\CreateAdminWithRoleAndPermission;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\StoreAdminRequest;
use App\Http\Requests\V1\Admin\UpdateAdminRequest;
use App\Mail\Admin\CompleteAdminRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::Admins, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::Admins, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::Admins, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::Admins, Action::Edit, Action::Manage])
        )->only('update');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::Admins, Action::Delete, Action::Manage])
        )->only('destroy');
    }

    /**
     * @param  GetPaginatedUsersByRole  $getPaginatedUsersByRole
     * @return JsonResponse
     */
    public function index(GetPaginatedUsersByRole $getPaginatedUsersByRole): JsonResponse
    {
        $admins = $getPaginatedUsersByRole->handle(Area::roles(Area::SuperAdmin));

        return fractal($admins, new UserTransformer(Area::SuperAdmin))
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
     * @param  User  $admin
     * @return JsonResponse
     */
    public function show(User $admin): JsonResponse
    {
        if (! $admin->hasRole(Area::roles(Area::SuperAdmin))) {
            throw UnauthorizedException::forRoles(Area::roles(Area::SuperAdmin));
        }

        $admin->load('permissions', 'roles');

        return fractal($admin, new UserTransformer(Area::SuperAdmin))
            ->parseIncludes([
                'id',
                'first_name',
                'last_name',
                'email',
                'role',
                'permissions',
                'phone_number',
                'phone_country_code',
                'formatted_phone_number',
            ])->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreAdminRequest  $createAdminRequest
     * @param  CreateAdminWithRoleAndPermission  $createAdminWithRoleAndPermission
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function store(
        StoreAdminRequest $request,
        CreateAdminWithRoleAndPermission $createAdminWithRoleAndPermission
    ): JsonResponse {
        $data = $request->validated();
        $data = $this->transformPermissions($data);

        return DB::transaction(function () use ($request, $data, $createAdminWithRoleAndPermission) {
            $admin = $createAdminWithRoleAndPermission->handle($data);

            Mail::to($admin)
                ->send(
                    new CompleteAdminRegisterInvitation(
                        $request->user(),
                        $admin,
                        $data['redirect_url']
                    )
                );

            $admin->load('permissions', 'roles');

            return fractal($admin, new UserTransformer(Area::SuperAdmin))
                ->parseIncludes([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role',
                    'permissions',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                ])->respond(Response::HTTP_CREATED);
        });
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  User  $admin
     * @param  UpdateAdminRequest  $updateAdminRequest
     * @param  UpdateAdminWithRoleAndPermission  $updateAdminWithRoleAndPermission
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function update(
        User $admin,
        UpdateAdminRequest $request,
        UpdateAdminWithRoleAndPermission $updateAdminWithRoleAndPermission,
    ): JsonResponse {
        return DB::transaction(function () use ($request, $admin, $updateAdminWithRoleAndPermission) {
            if (! $admin->hasRole(Area::roles(Area::SuperAdmin))) {
                throw UnauthorizedException::forRoles(Area::roles(Area::SuperAdmin));
            }

            $data = $request->validated();
            $data = $this->transformPermissions($data);

            DB::transaction(function () use ($updateAdminWithRoleAndPermission, $data, $admin) {
                $updateAdminWithRoleAndPermission->handle($data, $admin);
            });

            return fractal($admin, new UserTransformer(Area::SuperAdmin))
                ->parseIncludes([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role',
                    'permissions',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                ])
                ->respond();
        });
    }

    /**
     * @param  User  $admin
     * @return JsonResponse
     */
    public function destroy(User $admin): JsonResponse
    {
        if (! $admin->hasRole(Area::roles(Area::SuperAdmin))) {
            throw UnauthorizedException::forRoles(Area::roles(Area::SuperAdmin));
        }

        $admin->update(['email' => $admin->getEmailForSoftDeleting()]);
        $admin->delete();

        return $this->successResponse();
    }

    protected function transformPermissions(array $data): array
    {
        if ($data['role'] == Role::Admin) {
            unset($data['permissions']);
        } else {
            $data['permissions'] = Grantify::transformToAreaSubject(Area::SuperAdmin, $data['permissions']);
        }

        return $data;
    }
}
