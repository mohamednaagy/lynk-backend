<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\Util\Base64UrlSafe;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class JWTService
{
    private string $secret;

    private JWSBuilder $builder;

    private CompactSerializer $serializer;

    private JWK $key;

    private JWSVerifier $verifier;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $algManager = new AlgorithmManager([new HS256]);

        $this->builder = new JWSBuilder($algManager);
        $this->verifier = new JWSVerifier($algManager);
        $this->serializer = new CompactSerializer;

        $this->key = new JWK([
            'kty' => 'oct',
            'k' => Base64UrlSafe::encodeUnpadded($this->secret),
        ]);
    }

    public function generateToken(User $user): array
    {
        [$ttlMinutes, $expiresIn] = $this->getTtl($user);
        $expiresAt = $expiresIn ? now()->addSeconds($expiresIn) : null;
        $version = $this->getTokenVersion($user);
        $jti = Str::uuid()->toString();

        $payload = [
            'sub' => $user->id,
            'version' => $this->getTokenVersion($user),
            'jti' => $jti,
        ];

        if ($expiresAt) {
            $payload['exp'] = $expiresAt->timestamp;
        }

        $payloadJson = json_encode($payload);

        $jws = $this->builder
            ->create()
            ->withPayload($payloadJson)
            ->addSignature($this->key, ['alg' => 'HS256'])
            ->build();

        $token = $this->serializer->serialize($jws);

        //        JWTAuth::factory()->setTTL($ttlMinutes);
        //        $token = JWTAuth::claims(['version' => $version])->fromUser($user);

        return [
            'type' => 'token',
            'token' => $token,
            'company_id' => $user->company?->id,
            'expires_in' => $expiresIn,
        ];
    }

    private function getTtl(User $user): ?int
    {
        if (
            $user->hasRole(Role::LenderApiUser) &&
            ($customTtl = $user->company?->getTokenExpireValue())
        ) {
            return $customTtl * 60;
        }

        return null;
    }

    public function getTokenVersion(User $user): int
    {
        if ($user->hasRole(Role::LenderApiUser)) {
            return $user->company->getTokenExpireVersion();
        }

        return 1;
    }
}
