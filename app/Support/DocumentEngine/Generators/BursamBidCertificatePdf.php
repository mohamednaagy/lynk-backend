<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\BursamProductCode;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasTraderOrder;
use Carbon\CarbonImmutable;

class BursamBidCertificatePdf extends BasePdfGenerator
{
    use HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::TtiHoldingCertificate;

    public function getStorageCallback(): callable
    {
        return function ($fileResource) {
            $currentTimeInUtcTz = CarbonImmutable::now();

            return $this->getTraderOrder()
                ->addMediaFromStream($fileResource)
                ->usingFileName("holding-cert-{$this->getTraderOrder()->reference}-{$currentTimeInUtcTz->toDateTimeString()}.pdf")
                ->toMediaCollection($this->collectionName);
        };
    }

    public function isGeneratedBefore(): bool
    {
        return $this->getTraderOrder()->hasMedia($this->collectionName);
    }

    public function getGeneratedBeforePath(): string
    {
        return $this->getTraderOrder()->getMedia($this->collectionName)->first()->getPath();
    }

    protected function prepareData(): array
    {
        $bidCertificateDetails = $this->getTraderOrder()->bid_certificate_details;

        return [
            'e_cert_no' => $bidCertificateDetails['e_cert_no'],
            'buyer' => $bidCertificateDetails['buyer'],
            'owner' => $bidCertificateDetails['owner'],
            'bid_no' => $bidCertificateDetails['bid_no'],
            'total_value' => number_unformat($bidCertificateDetails['total_value']),
            'total_value_myr_equivalent' => number_unformat($bidCertificateDetails['total_value_myr_equivalent']),
            'currency' => $bidCertificateDetails['currency'],
            'purchase_time_date' => $bidCertificateDetails['purchase_time_date'].'  Malaysia Time (MYT)',
            'value_date' => $bidCertificateDetails['value_date'].'  Malaysia Time (MYT)',
            'p_name' => in_array($bidCertificateDetails['p_name'], BursamProductCode::getValues())
                ? BursamProductCode::fromValue($bidCertificateDetails['p_name'])->description
                : $bidCertificateDetails['p_name'],
            'p_volume' => $bidCertificateDetails['p_volume'],
            'line' => $bidCertificateDetails['line'],
        ];
    }

    protected function getTemplatePath(): string
    {
        return 'bursam-templates.bid-certificate-template';
    }
}
