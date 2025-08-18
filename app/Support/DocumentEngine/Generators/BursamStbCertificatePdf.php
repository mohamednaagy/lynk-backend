<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\BursamProductCode;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasTraderOrder;
use Carbon\CarbonImmutable;

class BursamStbCertificatePdf extends BasePdfGenerator
{
    use HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::BursamTtiHoldingCertificate;

    public function getStorageCallback(): callable
    {
        return function ($fileResource) {
            $currentTimeInUtcTz = CarbonImmutable::now();

            return $this->getTraderOrder()
                ->addMediaFromStream($fileResource)
                ->usingFileName("stb-cert-{$this->getTraderOrder()->reference}-{$currentTimeInUtcTz->toDateTimeString()}.pdf")
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
        $stbData = $this->getTraderOrder()->original_stb;

        return [
            'e_cert_no' => $stbData['ECERTNO'],
            'seller' => $stbData['SELLER'],
            'buyer' => $stbData['BUYER'],
            'total_value' => number_unformat($stbData['TOTALVALUE']),
            'total_value_myr_equivalent' => number_unformat($stbData['PRICE_MYR_EQUIVALENT']) * number_unformat($stbData['PVOLUME']),
            'currency' => $stbData['CURRENCY'],
            'selling_time_date' => $stbData['SELLINGTIMEDATE'].'  Malaysia Time (MYT)',
            'value_date' => $stbData['VALUEDATE'].'  Malaysia Date (MYT)',
            'p_name' => in_array($stbData['PNAME'], BursamProductCode::getValues())
                ? BursamProductCode::fromValue($stbData['PNAME'])->description
                : $stbData['PNAME'],
            'p_volume' => $stbData['PVOLUME'],
            'line' => $stbData['LINE'],
        ];
    }

    protected function getTemplatePath(): string
    {
        return 'bursam-templates.stb-certificate-template';
    }
}
