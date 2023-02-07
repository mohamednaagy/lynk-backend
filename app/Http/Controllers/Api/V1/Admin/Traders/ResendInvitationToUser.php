<?php

namespace App\Http\Controllers\Api\V1\Admin\Traders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Traders\ResendInvitationRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ResendInvitationToUser extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::TraderUserInvitation, Action::Send, Action::Manage])
        );
    }

    /**
     * @param  ResendInvitationRequest  $request
     * @param  Company  $trader
     * @param  User  $user
     * @return JsonResponse
     */
    public function __invoke(ResendInvitationRequest $request, Company $trader, User $user): JsonResponse
    {
        if (is_null($user->password)) {
            $invitationUrl = $request->validated('redirect_url');
            Mail::to($user)->send(new CompleteRegisterInvitation($user, $invitationUrl, CompanyType::Trader));
        }

        return $this->successResponse();
    }
}
