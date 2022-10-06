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
            throw ValidationException::withMessages(['token' => trans('response.invalid_token')]);
        }

        return AuthorizationToken::query()->create($data);
    }

    /**
     * @return string
     */
    private function generateRandomToken(): string
    {
        return Str::random(config('otpify.authorized_token_length'));
    }

    /**
     * @param string $token
     * @param string $area
     * @return bool
     */
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
