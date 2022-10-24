<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\SendEmailVerificationRequest;
use App\Mail\VerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendEmailVerification extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  SendEmailVerificationRequest  $request
     * @return JsonResponse
     */
    public function __invoke(SendEmailVerificationRequest $request)
    {
        $user = auth()->user();
        if ($request->has('email')) {
            $user->update($request->safe(['email']));
            $user->save();
        }

        Mail::to($user->email)->send(new VerifyEmail($user, $request->safeInput('redirect_url')));

        return $this->successResponse();
    }
}
