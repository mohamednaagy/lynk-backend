<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Http\Resources\AuthResource;

class GetAuthUser extends Controller
{
 public function __invoke()
 {
     $user = Auth::user();

     return new AuthResource($user);
 }
}
