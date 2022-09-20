<?php

namespace Modules\Otpify\Drivers;

use Closure;
use Twilio\Rest\Client;
use Twilio\Http\CurlClient;
use Illuminate\Http\Request;
use Modules\Otpify\Models\OtpifyCode;
use Twilio\Exceptions\TwilioException;
use Modules\Otpify\Traits\CanOtpifyCode;
use Modules\Otpify\Contracts\Otpifiable;
use Twilio\Exceptions\ConfigurationException;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;

class TwilioSmsDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    /**
     * @param Request $request
     * @param Otpifiable $otpifiable
     * @param array $data
     * @return OtpifyCode
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $code = generateRandomCode(config('otpify.code_length'));
        $otpifyCode = $this->createOtpifyCode($code, $data);

        $receiverNumber = getOtpifiablePhoneNumber($otpifiable)->formatE164();
        $message = trans('otpify::phone.message', ['code' => $code, 'time' => $otpifyCode->expiration_date->diffInMinutes(now())]);

        $accountSid = config('otpify.drivers.twilio.sid');
        $authToken = config('otpify.drivers.twilio.token');
        $twilioNumber = config('otpify.drivers.twilio.from_phone');

        $client = new Client($accountSid, $authToken);
        $curlOptions = [
            CURLOPT_SSL_VERIFYHOST => config('otpify.drivers.twilio.ssl_verify_host'),
            CURLOPT_SSL_VERIFYPEER => config('otpify.drivers.twilio.ssl_verify_peer')
        ];

        $client->setHttpClient(new CurlClient($curlOptions));
        $client->messages->create($receiverNumber, [
            'from' => $twilioNumber,
            'body' => $message
        ]);

        return $otpifyCode;
    }

    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param Otpifiable $otpifiable
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool
    {
        return $otpifiable->doesRequireVerifyingByOtp($request);
    }

    /**
     * @param Request $request
     * @param $vid
     * @param $code
     * @param Closure|null $additionalCheckCallback
     * @return bool
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpCodeNotFoundException
     */
    public function verify(Request $request, $vid, $code, Closure $additionalCheckCallback = null): bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        $this->verifyOtpifyCode($otpifyCode, $request, $code, $additionalCheckCallback);
        $this->setOtpExpiredAt($otpifyCode);

        return true;
    }
}
