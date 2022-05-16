<?php

namespace Modules\Customers\Http\Controllers\api\auth;

use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Customers\Http\Requests\RegisterCustomerRequest;

class RegisterController extends Controller
{
    public function __invoke(RegisterCustomerRequest $request)
    {
        $validated = $request->safe()->only([
            'first_name',
            'last_name',
            'phone_number',
            'email',
            'password',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        $validated['phone_number'] = phone($validated['phone_number'], $request->input('phone_country_code'));

        User::create($validated);

        return response()->json([], 201);
    }
}
