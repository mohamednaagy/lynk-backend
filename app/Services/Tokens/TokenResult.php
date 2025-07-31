<?php

namespace App\Services\Tokens;

use Illuminate\Contracts\Support\Arrayable;

readonly class TokenResult implements Arrayable
{
    public function __construct(
        public string $type,
        public string $token,
        public ?int $companyId,
        public ?int $expiresIn,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'token' => $this->token,
            'company_id' => $this->companyId,
            'expires_in' => $this->expiresIn,
        ];
    }
}
