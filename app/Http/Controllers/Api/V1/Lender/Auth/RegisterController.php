<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\RegisterLender;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\RegisterRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Modules\Grantify\Facades\Grantify;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterLender $registerLender)
    {
        $validated = $request->safe();
        return $this->successResponse(
            $registerLender->handle($validated->toArray()),
            Response::HTTP_CREATED
        );
    }
}
