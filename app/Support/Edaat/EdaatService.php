<?php

namespace App\Support\Edaat;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EdaatService
{
    public function createInvoice($id, $amount)
    {
        $response = Http::edaat()
            ->post($this->prefixedPath('Invoices/Single'), [
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

    public function isPaidInvoice($invoiceId)
    {
        $response = Http::edaat()
            ->get($this->prefixedPath('Invoices/'.$invoiceId.'/Details'));

        if ($this->isSuccess($response)) {
            return $response->json('Body.StatusEn') === 'Paid';
        }

        return false;
    }

    public function registerWebhook(?string $paymentUrl, ?string $billUrl, ?string $reconcileUrl)
    {
        $isPaymentSuccess = true;
        if ($paymentUrl) {
            $responsePayment = Http::edaat()
                ->withBody("\"$paymentUrl\"", 'application/json')
                ->post($this->prefixedPath('endpoints/PaymentNotification'));

            $isPaymentSuccess = $this->isSuccess($responsePayment);
        }

        $isBillSuccess = true;
        if ($billUrl) {
            $responseBill = Http::edaat()
                ->withBody("\"$billUrl\"", 'application/json')
                ->post($this->prefixedPath('endpoints/BillConfirmation'));

            $isBillSuccess = $this->isSuccess($responseBill);
        }

        $isReconcileSuccess = true;
        if ($reconcileUrl) {
            $responseReconcile = Http::edaat()
                ->withBody("\"$reconcileUrl\"", 'application/json')
                ->post($this->prefixedPath('endpoints/Reconciliation'));

            $isReconcileSuccess = $this->isSuccess($responseReconcile);
        }

        return $isPaymentSuccess && $isReconcileSuccess && $isBillSuccess;
    }

    private function isSuccess(Response $response)
    {
        return $response->json('Status.Success') && $response->json('Status.Code') === 'E000';
    }

    private function prefixedPath(string $path)
    {
        return '/api/v2/'.$path;
    }
}
