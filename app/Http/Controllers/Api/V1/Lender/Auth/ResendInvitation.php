<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\ResendInvitationRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Support\Facades\Mail;

class ResendInvitation extends Controller
{
    public function __invoke(ResendInvitationRequest $request, User $user)
    {
        if (is_null($user->password)) {
            $invitationUrl = $request->safeInput('redirect_url');
            Mail::to($user->email)->send(new CompleteRegisterInvitation($user, $invitationUrl));

            return fractal($user, new UserTransformer())->respond();
        }

        return $this->successResponse();
    }
}
