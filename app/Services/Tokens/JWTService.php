<?php

namespace App\Services\Tokens;

use App\Models\User;
use Illuminate\Support\Str;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

class JWTService implements TokenGeneratorInterface
{
    public function __construct(
        private readonly string $secret,
        private readonly JWSBuilder $builder,
        private readonly CompactSerializer $serializer,
        private readonly JWK $key,
        private readonly TokenConfigResolver $configResolver,
    ) {}

    /**
     * Generate a signed JWT token for the given user.
     */
    public function generateToken(User $user): TokenResult
    {
        $ttl = $this->configResolver->getTtlInSeconds($user);
        $issuedAt = now()->timestamp;
        $expiresAt = $ttl ? $issuedAt + $ttl : null;

        $payload = $this->buildPayload($user, $issuedAt, $expiresAt);

        $jws = $this->builder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($this->key, ['alg' => 'HS256'])
            ->build();

        $token = $this->serializer->serialize($jws);

        return new TokenResult(
            type: 'token',
            token: $token,
            companyId: $user->company?->id,
            expiresIn: $ttl,
        );
    }

    /**
     * Build the payload for the JWT.
     */
    private function buildPayload(User $user, int $issuedAt, ?int $expiresAt): array
    {
        $payload = [
            'iss' => $this->configResolver->getTokenIssuer(),
            'iat' => $issuedAt,
            'sub' => $user->id,
            'version' => $this->configResolver->getTokenVersion($user),
            'jti' => Str::uuid()->toString(),
            'nbf' => $issuedAt,
        ];

        if ($expiresAt !== null) {
            $payload['exp'] = $expiresAt;
        }

        return $payload;
    }
}
