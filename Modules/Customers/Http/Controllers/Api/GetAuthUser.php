<?php

namespace Modules\Customers\Http\Controllers\api;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Customers\Http\Resources\AuthResource;

class GetAuthUser extends Controller
{

    public function __invoke()
    {
        $user = Auth::user();

        return new AuthResource($user);
    }
}
