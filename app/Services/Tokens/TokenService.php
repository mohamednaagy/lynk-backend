<?php

namespace App\Services\Tokens;

use App\Models\User;
use App\Services\Tokens\Config\TokenConfigResolver;
use App\Services\Tokens\Contracts\TokenGeneratorInterface;
use Illuminate\Support\Str;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer as JWSCompactSerializer;

/**
 * Combined JWT (JWS) and JWE Token Service
 *
 * Provides both signing (JWS) and encryption (JWE) capabilities using the Jose library.
 * Supports configurable TTL, custom claims, and various encryption algorithms.
 */
class TokenService implements TokenGeneratorInterface
{
    public const MODE_SIGN = 'sign';

    public const MODE_ENCRYPT = 'encrypt';

    public const MODE_SIGN_ENCRYPT = 'sign_encrypt';

    private const JWS_ALGORITHM = 'HS256';

    private const TOKEN_TYPE = 'token';

    public function __construct(
        private readonly JWSBuilder $jwsBuilder,
        private readonly JWK $signingKey,
        private readonly TokenConfigResolver $configResolver,
    ) {}

    /**
     * Generate a token for the given user (implements TokenGeneratorInterface).
     * Defaults to signed tokens for backward compatibility.
     *
     * @param  User  $user  The user to generate token for
     * @return TokenResult The generated token result
     */
    public function generateToken(User $user): TokenResult
    {
        return $this->generateTokenWithMode($user, self::MODE_SIGN);
    }

    /**
     * Generate a token for the given user with specified security mode.
     *
     * @param  User  $user  The user to generate token for
     * @param  string  $mode  Security mode: 'sign', 'encrypt', or 'sign_encrypt'
     * @return TokenResult The generated token result
     */
    public function generateTokenWithMode(User $user, string $mode): TokenResult
    {
        $ttl = $this->configResolver->getTtlInSeconds($user);
        $tokenData = $this->createTokenData($ttl);
        $payload = $this->buildPayload($user, $tokenData);

        $token = match ($mode) {
            self::MODE_SIGN => $this->createSignedToken($payload),
            self::MODE_ENCRYPT => $this->createEncryptedToken($payload),
            self::MODE_SIGN_ENCRYPT => $this->createSignedAndEncryptedToken($payload),
        };

        return new TokenResult(
            type: self::TOKEN_TYPE,
            token: $token,
            companyId: $user->lender?->id,
            expiresIn: $ttl,
        );
    }

    /**
     * Create token timing data.
     */
    private function createTokenData(?int $ttl): array
    {
        $issuedAt = now()->timestamp;
        $expiresAt = $ttl ? $issuedAt + $ttl : null;

        return [
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Build the JWT payload with standard and custom claims.
     */
    private function buildPayload(User $user, array $tokenData): array
    {
        $payload = [
            'iss' => $this->configResolver->getTokenIssuer(),
            'iat' => $tokenData['issued_at'],
            'nbf' => $tokenData['issued_at'],
            'sub' => $user->id,
            'jti' => Str::uuid()->toString(),
            'version' => $this->configResolver->getTokenVersion($user),
        ];

        // Add expiration if TTL is set
        if ($tokenData['expires_at'] !== null) {
            $payload['exp'] = $tokenData['expires_at'];
        }

        return $payload;
    }

    /**
     * Create and sign a JWS token.
     */
    private function createSignedToken(array $payload): string
    {
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload(json_encode($payload, JSON_THROW_ON_ERROR))
            ->addSignature($this->signingKey, ['alg' => self::JWS_ALGORITHM])
            ->build();

        $serializer = new JWSCompactSerializer;

        return $serializer->serialize($jws);
    }

    /**
     * Create an encrypted JWE token.
     */
    private function createEncryptedToken(array $payload): string
    {
        // TODO
        return '';
    }

    /**
     * Create a token that is both signed and encrypted (nested JWT).
     * First signs the payload, then encrypts the resulting JWS.
     */
    private function createSignedAndEncryptedToken(array $payload): string
    {
        // TODO
        return '';
    }
}
