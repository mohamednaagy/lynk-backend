<?php

namespace App\Support\Traders\Drivers;

use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\TraderHelperTrait;
use CodeDredd\Soap\Facades\Soap;
use SoapClient;

class BursamDriver extends DmccDriver implements TraderInterface
{
    use TraderHelperTrait;

    private SoapClient $soap;

    public function __construct()
    {
        $this->soap = Soap::buildClient('bursam');
    }
    //TODO get Auth

    private function prefixUrl($url): string
    {
        return 'https://bsasapi.bursamalaysia.com/svc/auth/authorize?client_id='.config('trader.providers.bursam.client_id').'&response_type='.config('trader.providers.bursam.response_type').'&redirect_uri=https://'.config('trader.providers.bursam.client_id').'com.my/webservice/BsasRcv';
    }

    //TODO issue code

    public function getIssueMessage()
    {
    }
    //TODO request Access token

    //TODO respones access Token Token Duration

    //TODO call new order API with Access Token
}
