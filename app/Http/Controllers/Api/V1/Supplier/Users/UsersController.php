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
            'permission:' .
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Manage, Action::Create])
        )->only('store');
    }

    public function index(GetPaginatedSupplierUsers $getPaginatedUsers)
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

    public function store(StoreUserRequest $request, CreateSupplierUserWithRoleAndPermission $createSupplierUserWithRoleAndPermission)
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
