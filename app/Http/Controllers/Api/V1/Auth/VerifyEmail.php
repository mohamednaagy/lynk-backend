<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Contracts\VerifyEmail as VerifyEmailInterface;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmail extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'throttle:6,1']);
    }

    /**
     * Handle the incoming request.
     *
     * @param  VerifyEmailInterface  $verifyEmail
     * @param  Company  $company
     * @param  User  $user
     * @return JsonResponse
     */
    public function __invoke(Request $request, User $user)
    {
        if ($user->company_id !== (int) $request->query('company_id') || $user->email_verified_at) {
            return $this->errorResponse(__('auth.failed'));
        }

        $user->markEmailAsVerified();

        return $this->successResponse();
    }
}
