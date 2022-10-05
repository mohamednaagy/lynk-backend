<?php

namespace Modules\Otpify\Http\Controllers;

use App\ResponseCodes\Codes;
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
use Modules\Otpify\Exceptions\OtpifiableNotEqualAuthUserException;

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
            return $this->errorResponse(trans('response.something_went_wrong'), code: Codes::OTPIFY_DRIVERS_CONFIGURATION);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), code: Codes::GENERAL_CODE);
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
        $code = null;

        try {
            $valid = Otpify::driver($setting->otp_driver)->verify($otpifyRequest, $data['vid'], $data['code']);
            return $this->successResponse([]);
        } catch (OtpifiableNotEqualAuthUserException) {
            $message = trans('response.otpifiable_not_equal_auth_user');
            $code = Codes::OTPIFY_WRONG_USER;
        } catch (OtpCodeAlreadyUsedException) {
            $message = trans('response.otp_already_used');
            $code = Codes::OTPIFY_ALREADY_USED;
        } catch (OtpCodeAdditionalCheckException) {
            $message = trans('response.otp_code_additional_check_error');
            $code = Codes::OTPIFY_ADDITIONAL_CHECK;
        } catch (OtpCodeExpiredException) {
            $message = trans('response.otp_code_expired');
            $code = Codes::OTPIFY_EXPIRED;
        } catch (OtpCodeIncorrectException|OtpCodeNotFoundException) {
            $message = trans('response.otp_code_invalid');
            $code = Codes::OTPIFY_INVALID;
        }

        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED, $code);
    }
}
