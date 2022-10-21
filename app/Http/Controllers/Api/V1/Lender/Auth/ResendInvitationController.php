<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\ResendInvitationRequest;
use App\Mail\CompleteRegisterInvitation;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Support\Facades\Mail;

class ResendInvitationController extends Controller
{
    public function __invoke(ResendInvitationRequest $request)
    {
        $user = User::where('email', $request->safeInput('email'))->where('password', '=', null)->first();
        if ($user != null) {
            $user->email = $request->safeInput('email');
            $invitationUrl = $request->safeInput('redirect_url');
            Mail::to($user->email)->send(new CompleteRegisterInvitation($user, $invitationUrl));

            return fractal($user, new UserTransformer())->respond();
        }

        return $this->errorResponse(trans(' The user has accepted the invitation '));
    }
}
