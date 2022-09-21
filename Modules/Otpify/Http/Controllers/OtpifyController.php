<?php

namespace Modules\Otpify\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Otpify\Exceptions\OtpifiableNotEqualAuthUserException;
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
            $otpCode = Otpify::driver($setting->otp_driver)->send($otpifyRequest, $otpifyRequest->user());

            return $this->successResponse([
                'message' => trans('response.OTP_generated_successfully'),
                'vid' => $otpCode->id
            ]);
        } catch (ConfigurationException|TwilioException) {
            return $this->errorResponse(trans('response.something_went_wrong'));
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
        } catch (OtpifiableNotEqualAuthUserException) {
            $message = trans('response.otpifiable_not_equal_auth_user');
        } catch (OtpCodeAlreadyUsedException) {
            $message = trans('response.otp_already_used');
        } catch (OtpCodeAdditionalCheckException) {
            $message = trans('response.otp_code_additional_check_error');
        } catch (OtpCodeExpiredException) {
            $message = trans('response.otp_code_expired');
        } catch (OtpCodeIncorrectException|OtpCodeNotFoundException) {
            $message = trans('response.otp_code_invalid');
        }

        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED);
    }
}
