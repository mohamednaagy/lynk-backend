<?php

namespace Modules\Otpify\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Otpify\Models\AuthorizationToken;
use Illuminate\Validation\ValidationException;

trait CanBeAuthorized
{
    /**
     * @param array $data
     * @return Builder|Model|MessageBag
     * @throws ValidationException
     */
    private function createAuthorizationToken(array $data): Model|Builder|MessageBag
    {
        $validator = Validator::make($data, [
            'token' => ['unique:authorization_tokens,token'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(['token' => 'invalid token']);
        }

        return AuthorizationToken::query()->create($data);
    }

    /**
     * @return string
     */
    private function generateRandomToken(): string
    {
        return hash('sha256', Str::random(config('otpify.authorized_token_length')));
    }

    /**
     * @param string $token
     * @return bool
     */
    private function verifyToken(string $token): bool
    {
        return AuthorizationToken::query()->where('token', $token)->exists();
    }
}
