<?php

namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Actions\Contracts\LoginUser;
use App\Actions\Lenders\Contracts\RegisterLender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lenders\RegisterLenderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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
