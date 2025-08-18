<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\BursamProductCode;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasTraderOrder;
use Carbon\CarbonImmutable;

class BursamOtcCertificatePdf extends BasePdfGenerator
{
    use HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::BursamSellingCommodityToCustomer;

    public function getStorageCallback(): callable
    {
        return function ($fileResource) {
            $currentTimeInUtcTz = CarbonImmutable::now();

            return $this->getTraderOrder()
                ->addMediaFromStream($fileResource)
                ->usingFileName("otc-cert-{$this->getTraderOrder()->reference}-{$currentTimeInUtcTz->toDateTimeString()}.pdf")
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
        $otcData = $this->getTraderOrder()->otc_data;

        return [
            'e_cert_no' => $otcData['ECERTNO'],
            'seller' => $otcData['SELLER'],
            'buyer' => $otcData['BUYER'],
            'murabaha_value' => $otcData['MURABAHAVALUE'],
            'total_value' => number_unformat($otcData['TOTALVALUE']),
            'total_value_myr_equivalent' => number_unformat($otcData['PRICE_MYR_EQUIVALENT']) * number_unformat($otcData['PVOLUME']),
            'currency' => $otcData['CURRENCY'],
            'reporting_time_date' => $otcData['REPORTINGTIMEDATE'].'  Malaysia Time (MYT)',
            'value_date' => $otcData['VALUEDATE'].'  Malaysia Time (MYT)',
            'p_name' => in_array($otcData['PNAME'], BursamProductCode::getValues())
                ? BursamProductCode::fromValue($otcData['PNAME'])->description
                : $otcData['PNAME'],
            'p_volume' => $otcData['PVOLUME'],
            'line' => $otcData['LINE'],
        ];
    }

    protected function getTemplatePath(): string
    {
        return 'bursam-templates.otc-certificate-template';
    }
}
