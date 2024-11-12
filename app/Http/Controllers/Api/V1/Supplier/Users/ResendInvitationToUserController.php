<?php

namespace App\Http\Controllers\Api\V1\Supplier\Users;

use App\Actions\Contracts\ResendInvitationToUser as ResendInvitationToUserContract;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\CommoditySupplier\Users\ResendInvitationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ResendInvitationToUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:' .
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierUsers, Action::Create, Action::Manage])
        );

    }

    /**
     * Handle the incoming request to resend an invitation to a user.
     *
     * @param ResendInvitationRequest $request The request instance containing validated data.
     * @param User $user The user to whom the invitation will be resent.
     * @param ResendInvitationToUserContract $resendInvitationToUserContract The contract handling the resending of the invitation.
     *
     * @return JsonResponse A JSON response indicating the success of the operation.
     */
    public function __invoke(ResendInvitationRequest $request, User $user, ResendInvitationToUserContract $resendInvitationToUserContract): JsonResponse
    {
        if (is_null($user->password)) {
            $invitationUrl = $request->validated('redirect_url');
            $resendInvitationToUserContract->handle(tenant(), $user, $invitationUrl);
        }

        return $this->successResponse();
    }
}
