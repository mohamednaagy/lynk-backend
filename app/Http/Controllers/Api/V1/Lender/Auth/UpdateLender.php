<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Auth\UpdateLenderRequest;
use Illuminate\Http\Request;

class UpdateLender extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  UpdateUser  $updateUser
     * @param  UpdateLenderRequest  $updateLenderRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateUser $updateUser, UpdateLenderRequest $updateLenderRequest)
    {
        $updateUser->handle(auth()->user(), $updateLenderRequest->validated());

        return $this->successResponse();
    }
}
