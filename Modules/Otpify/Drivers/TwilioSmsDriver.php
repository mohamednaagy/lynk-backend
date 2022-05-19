<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Exception;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\OtpifiableCode;
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
     * @throws \ErrorException
     */
    public function execute(Request $request, Otpifiable $otpifiable, array $data): OtpifyCode
    {
        $code = generateRandomCode(config('otpify.code_length'));
        $otpifyCode = $this->createOtpifyCode($data, $code);

        if (method_exists($otpifiable, 'routeOtpForPhoneNumber'))
            $receiverNumber = $otpifiable->routeOtpForPhoneNumber()->formatE164();
        else
            $receiverNumber = $otpifiable->phone_number;

        $message = trans('otpify::phone.message', ['code' => $code, 'time' => $otpifyCode->expired_at->diffInMinutes(now())]);

        try {

            $account_sid = config("otpify.drivers.twilio.sid");
            $auth_token = config("otpify.drivers.twilio.token");
            $twilio_number = config("otpify.drivers.twilio.from_phone");

            $client = new Client($account_sid, $auth_token);

            $curlOptions = [
                CURLOPT_SSL_VERIFYHOST => config("otpify.drivers.twilio.ssl_verify_host"),
                CURLOPT_SSL_VERIFYPEER => config("otpify.drivers.twilio.ssl_verify_peer")
            ];
            $client->setHttpClient(new CurlClient($curlOptions));

            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);

            return $otpifyCode;

        } catch (Exception $e) {
            throw new \ErrorException("Error: ". $e->getMessage());
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
     * @return bool
     */
    public function verify(Request $request, $vid, $code): bool
    {
        $otpifyCode = $this->getOtpifyCode($vid, $code);
        return $this->isCodeExpired($otpifyCode->expiration_date);
    }
}
