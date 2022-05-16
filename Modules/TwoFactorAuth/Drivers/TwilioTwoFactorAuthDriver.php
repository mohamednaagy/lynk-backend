<?php

namespace Modules\TwoFactorAuth\Drivers;

use App\Models\User;
use Modules\TwoFactorAuth\Contracts\TwoFactorAuthInterface;
use Exception;
use Twilio\Http\CurlClient;
use Twilio\Rest\Client;

class TwilioTwoFactorAuthDriver implements TwoFactorAuthInterface
{

    /**
     * Execute the driver logic.
     *
     * @param
     * @return mixed
     */
    public function execute(User $user)
    {
        $receiverNumber = $user->phone_number;
        $message = "This is testing SMS from BIM";

        try {

            $account_sid = config("twofactorauth.drivers.twilio.sid");
            $auth_token = config("twofactorauth.drivers.twilio.token");
            $twilio_number = config("twofactorauth.drivers.twilio.from_phone");

            $client = new Client($account_sid, $auth_token);

            //Those two lines of code for ssl issue to send SMS in localhost
            $curlOptions = [ CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false];
            $client->setHttpClient(new CurlClient($curlOptions));

            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);

            dd('SMS Sent Successfully.');

        } catch (Exception $e) {
            dd("Error: ". $e->getMessage());
        }
    }
}
