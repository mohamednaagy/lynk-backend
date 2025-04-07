<?php

namespace Modules\Otpify\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Otpify\Models\AuthorizationToken;

trait CanBeAuthorized
{
    /**
     * @throws ValidationException
     */
    private function createAuthorizationToken(array $data): Model|Builder|MessageBag
    {
        $validator = Validator::make($data, [
            'token' => ['unique:authorization_tokens,token'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(['token' => trans('response.invalid_token')]);
        }

        return AuthorizationToken::query()->create($data);
    }

    private function generateRandomToken(): string
    {
        return Str::random(config('otpify.authorized_token_length'));
    }

    private function verifyToken(string $token, string $area): bool
    {
        $token = explode('|', $token);

        return AuthorizationToken::query()
            ->where([
                ['id', '=', $token[0]],
                ['token', '=', hash('sha265', $token[1])],
                ['area', '=', $area],
            ])
            ->exists();
    }
}
