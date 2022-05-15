<?php

namespace Modules\Customers\Http\Controllers\api;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{

    public function show()
    {
        $user = Auth::user();
        return response()->json([
            'data' => [
                'message' => 'retrived successfuly',
                "id" => $user->id,
                "email" => $user->email,
                "full_name" => $user->fullName,
            ]
        ], 200);
    }
}
