<?php

namespace App\Http\Controllers\Api\V1\Supplier\Users;

use App\Actions\Contracts\Commodities\CommoditySupplier\CreateSupplierUserWithRoleAndPermission;
use App\Actions\Contracts\Commodities\CommoditySupplier\GetPaginatedSupplierUsers;
use App\Http\Controllers\Controller;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Requests\V1\Supplier\CommoditySupplier\Users\StoreUserRequest;
use App\Mail\Supplier\CompleteSupplierRegisterInvitation;
use App\Transformers\UserTransformer;
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
}
