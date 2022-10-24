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
                'ExpiryDate' => now()->addDays(30)->toISOString(),
            ]);

        if ($this->isSuccess($response)) {
            return $response->json('Body.InvoiceNo');
        }

        return false;
    }

    public function registerWebhook(string $url)
    {
        $responsePayment = Http::edaat()
            ->withBody("\"$url\"", 'application/json')
            ->post(self::PREFIX.'endpoints/PaymentNotification');

        $responseBill = Http::edaat()
            ->withBody("\"$url\"", 'application/json')
            ->post(self::PREFIX.'endpoints/BillConfirmation');

        $responseReco = Http::edaat()
            ->withBody("\"$url\"", 'application/json')
            ->post(self::PREFIX.'endpoints/Reconciliation');

        return $this->isSuccess($responseBill) && $this->isSuccess($responsePayment) && $this->isSuccess($responseReco);
    }

    private function isSuccess(Response $response)
    {
        return $response->json('Status.Success') && $response->json('Status.Code') === 'E000';
    }
}
