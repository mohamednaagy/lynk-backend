<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Contracts\VerifyEmail as VerifyEmailInterface;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class VerifyEmail extends Controller
{
    public function __construct()
    {
        $this->middleware('signed', (array) 'throttle:6,1');
    }

    /**
     * Handle the incoming request.
     *
     * @param  VerifyEmailInterface  $verifyEmail
     * @param  Company  $company
     * @param  User  $user
     * @return JsonResponse
     */
    public function __invoke(VerifyEmailInterface $verifyEmail, Company $company, User $user)
    {
        if ($user->company_id !== $company->id) {
            return $this->errorResponse(__('auth.failed'));
        }

        $verifyEmail->handle($user);

        return $this->successResponse();
    }
}
