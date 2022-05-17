<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Exception;
use Modules\Otpify\Entities\OtpifyCode;
use Modules\Otpify\Traits\GenerateOtpifyCode;
use Twilio\Http\CurlClient;
use Twilio\Rest\Client;

class TwilioOtpifyDriver implements OtpifyDriverInterface
{
    use GenerateOtpifyCode;

    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param Otpifiable $model
     * @param array $data
     * @return OtpifyCode
     * @throws \ErrorException
     */
    public function execute(Request $request, Otpifiable $model, array $data): OtpifyCode
    {
        $otpifyCode = $this->createOtpifyCode($data);

        $receiverNumber = "+201221580037";
        $message = 'Your OTP Code is: '. $otpifyCode->otp_code .', It will be expired in '. $otpifyCode->expired_at->diffInMinutes(now()).' Minutes';

        try {

            $account_sid = config("otpify.drivers.twilio.sid");
            $auth_token = config("otpify.drivers.twilio.token");
            $twilio_number = config("otpify.drivers.twilio.from_phone");

            $client = new Client($account_sid, $auth_token);

            //Those two lines of code for ssl issue to send SMS in localhost
            $curlOptions = [ CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false];
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

    public function verify(Request $request, $vid, $code): bool
    {
        // TODO: Implement verify() method.
    }
}
