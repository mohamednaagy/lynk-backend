<?php

namespace App\Support\Edaat;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EdaatService
{
    private const PREFIX = '/api/v2/';

    public function createInvoice($id, $amount)
    {
        $response = Http::edaat()
            ->post(self::PREFIX.'Invoices/Single', [
                'IsClientEnterpise' => true,
                'RegistrationNo' => config('edaat.company'),
                'InternalCode' => $id,
                'IssueDate' => now()->toISOString(),
                'DueDate' => now()->toISOString(),
                'TotalAmount' => $amount,
                'Products' => [
                    [
                        'ProductCode' => config('edaat.product'),
                        'Price' => $amount,
                        'Qty' => 1,
                        'DiscountPercentage' => 0,
                    ],
                ],
                'HasValidityPeriod' => true,
                'FromDurationTime' => '00:00',
                'ToDurationTime' => '23:59',
                'ExportToSadad' => true,
                'ExpiryDate' => now()->addDay()->toISOString(),
                'SubBillerShareAmount' => 0,
                'SubBillerSharePercentage' => 0,
            ]);

        if ($this->isSuccess($response)) {
            return $response->json('Body.InvoiceNo');
        }

        return false;
    }

    public function registerWebhook(string $url)
    {
        $response = Http::edaat()
            ->withBody("\"$url\"", 'application/json')
            ->post(self::PREFIX.'endpoints/PaymentNotification');

        return $this->isSuccess($response);
    }

    private function isSuccess(Response $response)
    {
        return $response->json('Status.Success') && $response->json('Status.Code') === 'E000';
    }
}
