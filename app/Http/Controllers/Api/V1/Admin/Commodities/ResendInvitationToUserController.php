<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\ResendInvitationToUser as ResendInvitationToUserContract;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\Users\ResendInvitationRequest;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ResendInvitationToUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommoditySupplierUsers, Action::Create, Action::Manage])
        );

    }

    public function __invoke(ResendInvitationRequest $request, Supplier $supplier, User $user, ResendInvitationToUserContract $resendInvitationToUserContract): JsonResponse
    {
        if (is_null($user->password)) {
            $invitationUrl = $request->validated('redirect_url');
            $resendInvitationToUserContract->handle($supplier, $user, $invitationUrl);
        }

        return $this->successResponse();
    }
}
