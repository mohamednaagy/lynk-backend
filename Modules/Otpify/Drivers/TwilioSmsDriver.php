<?php

namespace Modules\Otpify\Drivers;

use Closure;
use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Exceptions\OtpifiableNotEqualAuthUserException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\CanOtpifyCode;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Http\CurlClient;
use Twilio\Rest\Client;

class TwilioSmsDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    /**
     * @param  Request  $request
     * @param  Otpifiable  $otpifiable
     * @param  array  $data
     * @return OtpifyCode
     *
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $code = generateRandomCode(config('otpify.code_length'));
        $otpifiable = $request->get('otpifiable_id') ?? auth()->user();
        $otpifyCode = $this->createOtpifyCode($code, $otpifiable, auth()->user(), $data);

        $receiverNumber = getOtpifiablePhoneNumber($otpifiable)->formatE164();
        $message = trans('otpify::phone.message', ['code' => $code, 'time' => $otpifyCode->expiration_date->diffInMinutes(now())]);

        $accountSid = config('otpify.drivers.twilio.sid');
        $authToken = config('otpify.drivers.twilio.token');
        $twilioNumber = config('otpify.drivers.twilio.from_phone');

        $client = new Client($accountSid, $authToken);
        $curlOptions = [
            CURLOPT_SSL_VERIFYHOST => config('otpify.drivers.twilio.ssl_verify_host'),
            CURLOPT_SSL_VERIFYPEER => config('otpify.drivers.twilio.ssl_verify_peer'),
        ];

        $client->setHttpClient(new CurlClient($curlOptions));
        $client->messages->create($receiverNumber, [
            'from' => $twilioNumber,
            'body' => $message,
        ]);

        return $otpifyCode;
    }

    /**
     * Execute the driver logic.
     *
     * @param  Request  $request
     * @param  Otpifiable  $otpifiable
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool
    {
        return $otpifiable->doesRequireVerifyingByOtp($request);
    }

    /**
     * @param  Request  $request
     * @param $vid
     * @param $code
     * @param  Closure|null  $additionalCheckCallback
     * @return bool
     *
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpCodeNotFoundException
     * @throws OtpifiableNotEqualAuthUserException
     */
    public function verify(Request $request, $vid, $code, Closure $additionalCheckCallback = null): bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        $this->verifyOtpifyCode($otpifyCode, $request, $code, $additionalCheckCallback);
        $this->setOtpExpiredAt($otpifyCode);
        $this->createAuthorizationToken($request->all());

        return true;
    }
}
