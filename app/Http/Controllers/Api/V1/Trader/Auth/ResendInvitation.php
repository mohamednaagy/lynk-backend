<?php

namespace App\Http\Controllers\Api\V1\Trader\Auth;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Auth\ResendInvitationRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ResendInvitation extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::TraderUsers, Action::Create, Action::Manage])
        );
    }

    public function __invoke(ResendInvitationRequest $request, User $user): JsonResponse
    {
        if (is_null($user->password)) {
            $invitationUrl = $request->validated('redirect_url');
            Mail::to($user)->send(new CompleteRegisterInvitation($user, $invitationUrl, CompanyType::Trader));
        }

        return $this->successResponse();
    }
}
