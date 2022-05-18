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

class TwilioOtpifyDriver implements OtpifyDriverInterface
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
        $otpifyCode = $this->createOtpifyCode($data);

        $receiverNumber = $otpifiable->phone_number;
        $message = trans('otpify::phone.message', ['code' => $otpifyCode->otp_code, 'time' => $otpifyCode->expired_at->diffInMinutes(now())]);

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
        return $this->codeIsValid($otpifyCode->expired_at);
    }
}
