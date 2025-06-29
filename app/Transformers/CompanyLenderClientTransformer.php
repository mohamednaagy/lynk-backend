<?php

namespace App\Transformers;

use App\Enums\MediaCollections\ClientAutoSellPeriodMediaCollection;
use App\Models\CompanyLenderClient;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class CompanyLenderClientTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'name',
        'type',
        'national_id',
        'auto_complete_sell',
        'auto_sell_periods',
    ];

    public function transform(CompanyLenderClient $companyLenderClient): array
    {
        return [];
    }

    public function includeId(CompanyLenderClient $companyLenderClient): Primitive
    {
        return $this->primitive($companyLenderClient->id);
    }

    public function includeName(CompanyLenderClient $companyLenderClient): Primitive
    {
        return $this->primitive($companyLenderClient->name);
    }

    public function includeNationalId(CompanyLenderClient $companyLenderClient): Primitive
    {
        return $this->primitive($companyLenderClient->national_id);
    }

    public function includeType(CompanyLenderClient $companyLenderClient): Primitive
    {
        return $this->primitive([
            'value' => $companyLenderClient->type->value,
            'description' => $companyLenderClient->type->description,
        ]);
    }

    public function includeAutoCompleteSell(CompanyLenderClient $companyLenderClient): Primitive
    {
        return $this->primitive($companyLenderClient->auto_complete_sell);
    }

    public function includeAutoSellPeriods(CompanyLenderClient $companyLenderClient): Collection
    {
        $autoSellPeriods = $companyLenderClient->autoSellPeriods->map(function ($period) {
            $supportingDocuments = $period->getMedia(ClientAutoSellPeriodMediaCollection::SupportingDocument)->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'collection_name' => $media->collection_name,
                    'url' => $media->getUrl(),
                ];
            });

            return [
                'id' => $period->id,
                'effective_start' => $period->effective_start,
                'effective_end' => $period->effective_end,
                'supporting_documents' => $supportingDocuments,
            ];
        });

        return $this->collection($autoSellPeriods, function ($period) {
            return $period;
        });
    }
}
