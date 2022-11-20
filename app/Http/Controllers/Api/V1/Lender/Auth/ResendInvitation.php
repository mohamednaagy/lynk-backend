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
            $invitationUrl = $request->validated('redirect_url');

            // __REVIEW__ pass $user object instead of $user->email so that Mail can utilize $user->locale
            Mail::to($user->email)->send(new CompleteRegisterInvitation($user, $invitationUrl));

            // __REVIEW__ no need to return here. The return on line 26 is enough
            return fractal($user, new UserTransformer())->respond();
        }

        return $this->successResponse();
    }
}
