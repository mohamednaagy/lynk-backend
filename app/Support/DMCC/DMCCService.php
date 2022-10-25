<?php

namespace App\Support\DMCC;

use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use RuntimeException;

class DMCCService
{
    private SoapClient $soap;

    public function __construct()
    {
        $this->soap = Soap::withBasicAuth(config('dmcc.username'), config('dmcc.password'));
    }

    public function acceptAgreemt()
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getClickThroughAgreement'))
            ->call('getClickThroughAgreement');

        if ($error = $response->collect()->get('errorMessage')) {
            throw new RuntimeException($error);
        }

        $response = $this->soap
            ->baseWsdl($this->prefixUrl('acceptRejectClickThroughAgreement'))
            ->call('acceptRejectClickThroughAgreement', [
                'acceptReject' => 'true',
            ]);

        return $this->isSuccess($response);
    }

    private function prefixUrl($url)
    {
        return 'https://na2.ai.dm-us.informaticacloud.com/active-bpel/soap/'.$url.'?wsdl';
    }

    private function isSuccess(Response $response)
    {
        return $response->successful() && $response->json()['successCode'] === '0000';
    }
}
