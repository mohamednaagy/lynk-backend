<?php

namespace Modules\Otpify\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Otpify\Facades\Otpify;
use App\Http\Controllers\Controller;
use Twilio\Exceptions\TwilioException;
use Twilio\Exceptions\ConfigurationException;
use Symfony\Component\HttpFoundation\Response;
use Modules\Otpify\Http\Requests\OtpifyRequest;
use App\Actions\Contracts\GetSettingsClassInstance;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;

class OtpifyController extends Controller
{
    public function __construct(protected GetSettingsClassInstance $getSettingsClassInstance)
    {
    }

    /**
     * @param OtpifyRequest $otpifyRequest
     * @return JsonResponse
     */
    public function generateOtp(
        OtpifyRequest $otpifyRequest
    ): JsonResponse
    {
        try {
            $setting = $this->getSettingsClassInstance->handle($otpifyRequest->validated('area'));
            $otpCode = Otpify::driver($setting->otp_driver)->execute($otpifyRequest, $otpifyRequest->user());

            return $this->successResponse([
                'message' => 'OTP generated successfully',
                'otp_code' => $otpCode
            ]);
        } catch (ConfigurationException|TwilioException) {
            return $this->errorResponse('something went wrong, try again later');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }

    /**
     * @param OtpifyRequest $otpifyRequest
     * @return JsonResponse
     */
    public function verifyOtpCode(OtpifyRequest $otpifyRequest): JsonResponse
    {
        $data = $otpifyRequest->validated();
        $setting = $this->getSettingsClassInstance->handle($data['area']);
        $message = '';

        try {
            $valid = Otpify::driver($setting->otp_driver)->verify($otpifyRequest, $data['vid'], $data['code']);
            return $this->successResponse([]);
        } catch (OtpCodeAlreadyUsedException) {
            $message = 'otp already used';
        } catch (OtpCodeAdditionalCheckException) {
            $message = 'otp code additional check error';
        } catch (OtpCodeExpiredException) {
            $message = 'otp code expired';
        } catch (OtpCodeIncorrectException|OtpCodeNotFoundException) {
            $message = 'otp code invalid';
        }

        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED);
    }
}
