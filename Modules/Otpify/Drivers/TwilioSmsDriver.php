<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpifyTwilioException;
use Modules\Otpify\Exceptions\OtpifyVerificationException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\OtpifiableCode;
use Throwable;
use Twilio\Http\CurlClient;
use Twilio\Rest\Client;

class TwilioSmsDriver implements OtpifyDriverInterface
{
    use OtpifiableCode;

    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param Otpifiable $otpifiable
     * @param array $data
     * @return OtpifyCode
     * @throws OtpifyTwilioException
     */
    public function execute(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $code = generateRandomCode(config('otpify.code_length'));
        $otpifyCode = $this->createOtpifyCode($code, $data);

        $receiverNumber = getOtpifiablePhoneNumber($otpifiable);

        $message = trans('otpify::phone.message', ['code' => $code, 'time' => $otpifyCode->expiration_date->diffInMinutes(now())]);

        try {

            $account_sid = config('otpify.drivers.twilio.sid');
            $auth_token = config('otpify.drivers.twilio.token');
            $twilio_number = config('otpify.drivers.twilio.from_phone');

            $client = new Client($account_sid, $auth_token);

            $curlOptions = [
                CURLOPT_SSL_VERIFYHOST => config('otpify.drivers.twilio.ssl_verify_host'),
                CURLOPT_SSL_VERIFYPEER => config('otpify.drivers.twilio.ssl_verify_peer')
            ];
            $client->setHttpClient(new CurlClient($curlOptions));

            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);

            return $otpifyCode;

        } catch (Throwable  $e) {
            throw new OtpifyTwilioException("Error: ". $e->getMessage(), 500, ['driver' => 'Twilio']);
        }
    }

    public function shouldAsk(Request $request, Otpifiable $model): bool
    {
        // TODO: Implement shouldAsk() method.
    }

    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param $vid
     * @param $code
     * @param \Closure|null $additionalCheckCallback
     * @return bool
     * @throws OtpifyVerificationException
     */
    public function verify(Request $request, $vid, $code, \Closure $additionalCheckCallback = null): bool
    {
        $otpifyCode = $otpifyCode = $this->getOtpifyCode($vid);

        $verificationMessage = $this->verifyOtpifyCode($otpifyCode, $code, $additionalCheckCallback);
        if ($verificationMessage !== 'valid')
            throw new OtpifyVerificationException($verificationMessage, 400, ['driver' => 'Twilio']);

        $this->setOtpExpiredAt($otpifyCode);
        return true;
    }
}
