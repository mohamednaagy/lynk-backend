<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\SendMessageRequest;
use App\Support\Sms\Sms;

class SmsController extends Controller
{
    public function sendMessage(SendMessageRequest $request)
    {
        //need it to do test
        $sms = Sms::sendSMS('$request', '966532702700');

        return  $sms;
    }
}
