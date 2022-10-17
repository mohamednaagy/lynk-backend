<?php

namespace App\Http\Controllers\Api\V1\Lender\Users;

use App\Actions\Contracts\Lenders\GetPaginatedLenders;
use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;

class UserController extends Controller
{
    public function index(GetPaginatedLenders $getPaginatedLenders)
    {
        return fractal($getPaginatedLenders->handle(), new UserTransformer)->respond();
    }
}
