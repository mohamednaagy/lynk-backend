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
        $this->soap = Soap::buildClient('dmcc');
    }

    public function acceptAgreemt(): bool
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

    public function getTTI(string $costPrice, string $profit)
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getTTIIDForIssuePTP'))
            ->call('getTTIIDForIssuePTP', [
                'currency' => 'SAR',
                'costPrice' => $costPrice,
                'profit' => $profit,
                'paymentTerms' => config('dmcc.tti.payment_terms'),
                'unitOfDuration' => config('dmcc.tti.unit_of_duration'),
                'product' => null,
                'registeredMember' => 'BOLFT',
                'client' => null,
            ]);

        return $response->body();
    }

    private function prefixUrl($url)
    {
        return 'https://'.config('dmcc.username').':'.config('dmcc.password').'@na2.ai.dm-us.informaticacloud.com/active-bpel/soap/'.$url.'?wsdl';
//        return 'https://na2.ai.dm-us.informaticacloud.com/active-bpel/soap/'.$url.'?wsdl';
    }

    private function isSuccess(Response $response)
    {
        return $response->successful() && $response->json()['successCode'] === '0000';
    }
}
