<?php

namespace App\Http\Controllers\Api\V1\Customers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request)
    {
        $validated = $request->safe()->only([
            'first_name',
            'last_name',
            'phone_number',
            'phone_country_code',
            'email',
            'password',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['phone_number'] = phone($validated['phone_number'], $validated['phone_country_code']);

        User::create($validated);

        return response()->json([], 201);
    }
}
