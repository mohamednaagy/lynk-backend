<?php

namespace Modules\Otpify\Traits;

use App\Enums\VerificationMethod;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Drivers\AbsherDriver;
use Modules\Otpify\Drivers\EmailDriver;
use Modules\Otpify\Drivers\FakeAbsherDriver;
use Modules\Otpify\Drivers\TwilioSmsDriver;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Exceptions\OtpifiableNotEqualAuthUserException;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;

trait CanOtpifyCode
{
    /**
     *  createOtpifyCode
     */
    public function createOtpifyCode(
        ?string $code,
        Otpifiable $otpifiable,
        Model|Otpifiable|null $initiator = null,
        array $data = []
    ): OtpifyCode {
        return OtpifyCode::create(array_merge([
            'id' => (string) Str::uuid(),
            'initiator_id' => optional($initiator)->getKey(),
            'initiator_type' => optional($initiator)->getMorphClass(),
            'otpifiable_id' => $otpifiable->getKey(),
            'otpifiable_type' => $otpifiable->getMorphClass(),
            'otp_code' => $code,
            'expiration_date' => now()->addMinutes(config('otpify.code_expiration_time')),
            'data' => $data,
            'driver' => $this->getDriverName(),
            'verification_method' => $this->getVerificationMethod(),
        ], $data));
    }

    /**
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpifiableNotEqualAuthUserException
     */
    public function verifyOtpifyCode(OtpifyCode $otpifyCode, Request $request, $code, ?Closure $additionalCheckCallback = null): void
    {
        if (auth()->user()->getAuthIdentifier() !== $otpifyCode->otpifiable_id) {
            throw new OtpifiableNotEqualAuthUserException();
        }

        if (! Hash::check($code, $otpifyCode->otp_code)) {
            throw new OtpCodeIncorrectException();
        }

        if ($otpifyCode->expired_at != null) {
            throw new OtpCodeAlreadyUsedException();
        }

        if ($this->isCodeExpired($otpifyCode->expiration_date)) {
            throw new OtpCodeExpiredException();
        }

        if ($additionalCheckCallback) {
            if (! $additionalCheckCallback($request, $otpifyCode)) {
                throw new OtpCodeAdditionalCheckException();
            }
        }
    }

    /**
     * @throws OtpCodeNotFoundException
     */
    public function getOtpifyCode($vid): OtpifyCode
    {
        $otpifyCode = OtpifyCode::where('id', $vid)->first();

        if (! $otpifyCode) {
            throw new OtpCodeNotFoundException();
        }

        return $otpifyCode;
    }

    public function isCodeExpired($expirationDate): bool
    {
        return $expirationDate->lt(now());
    }

    public function setOtpExpiredAt(OtpifyCode $otpifyCode): void
    {
        $otpifyCode->update(['expired_at' => now()]);
    }

    public function createAuthorizationToken(array $data): string
    {
        return Otpify::generateAuthorizationToken($data);
    }

    private function getDriverName(): string
    {
        $driverNameArray = explode('\\', get_class());
        $driverName = end($driverNameArray);
        $parts = explode('Driver', $driverName);

        return Str::snake($parts[0]);
    }

    private function getVerificationMethod(): string
    {
        return match (get_class()) {
            TwilioSmsDriver::class, AbsherDriver::class, FakeAbsherDriver::class => VerificationMethod::Phone,
            EmailDriver::class => VerificationMethod::Email,
        };
    }
}
