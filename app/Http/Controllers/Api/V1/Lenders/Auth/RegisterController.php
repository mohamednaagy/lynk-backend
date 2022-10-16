<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Actions\Contracts\LoginUser;
use App\Http\Controllers\Controller;
use App\Actions\Contracts\Lenders\RegisterLender;
use App\Http\Requests\V1\Lender\Auth\RegisterLenderRequest;

class RegisterController extends Controller
{
    public function __invoke(
        RegisterLenderRequest $request,
        RegisterLender $registerLender,
        LoginUser $loginUser
    ): JsonResponse {
        return DB::transaction(function () use ($loginUser, $request, $registerLender) {
            $lender = $registerLender->handle($request->validated());

            return $this->successResponse(
                $loginUser->handle($lender, $request->source, $request),
                Response::HTTP_CREATED
            );
        });
    }
}
