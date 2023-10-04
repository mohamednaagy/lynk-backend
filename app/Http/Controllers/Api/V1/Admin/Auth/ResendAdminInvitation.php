<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\ResendInvitationRequest;
use App\Mail\Admin\CompleteAdminRegisterInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ResendAdminInvitation extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Admins, Action::Create, Action::Manage])
        );
    }

    public function __invoke(ResendInvitationRequest $request, User $admin)
    {
        if (is_null($admin->password)) {
            $invitationUrl = $request->validated('redirect_url');
            Mail::to($admin)
                ->send(
                    new CompleteAdminRegisterInvitation(
                        $request->user(),
                        $admin,
                        $invitationUrl
                    )
                );
        }

        return $this->successResponse();
    }
}
