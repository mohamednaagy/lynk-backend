<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\CanOtpifyCode;
use Twilio\Http\CurlClient;
use Twilio\Rest\Client;

class TwilioSmsDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param Otpifiable $otpifiable
     * @param array $data
     * @return OtpifyCode
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    public function execute(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
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
     * Execute the driver logic.
     *
     * @param Request $request
     * @param $vid
     * @param $code
     * @param \Closure|null $additionalCheckCallback
     * @return bool
     * @throws \Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException
     * @throws \Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException
     * @throws \Modules\Otpify\Exceptions\OtpCodeExpiredException
     * @throws \Modules\Otpify\Exceptions\OtpCodeIncorrectException
     * @throws \Modules\Otpify\Exceptions\OtpCodeNotFoundException
     */
    public function verify(Request $request, $vid, $code, \Closure $additionalCheckCallback = null): bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        $this->verifyOtpifyCode($otpifyCode, $request, $code, $additionalCheckCallback);
        $this->setOtpExpiredAt($otpifyCode);

        return true;
    }
}
