<?php

namespace Modules\Customers\Http\Controllers\api;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Http\Requests\RegisterCustomerRequest;

class CustomerAuthController extends Controller
{
    public function register(RegisterCustomerRequest $request)
    {
        $validated = $request->safe()->only([
            'first_name',
            'last_name',
            'phone_country_code',
            'phone_number',
            'email',
            'password',
        ]);
        $validated['password'] = Hash::make($validated['password']);


        // User::create($validated); after create user module

        return response()->json([
            'data' => [
                'message' => 'registered successfuly',
                "data" => $validated
            ]
        ], 201);
    }
}
