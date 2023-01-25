<?php

namespace App\Transformers;

use App\Models\Company;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class CompanyTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'name',
        'unique_name',
        'company_cr',
        'does_order_require_approval',
        'status',
        'orders_count',
        'created_at',
        'order_cost',
        'webhook_secret_key',
        'public_status_comment',
        'driver',
    ];

    public function transform(Company $company): array
    {
        return [

        ];
    }

    public function includeId(Company $company): Primitive
    {
        return $this->primitive($company->id);
    }

    public function includeName(Company $company): Primitive
    {
        return $this->primitive($company->name);
    }

    public function includeUniqueName(Company $company): Primitive
    {
        return $this->primitive($company->unique_name);
    }

    public function includeOrderCost(Company $company): Primitive
    {
        return $this->primitive($company->order_cost->formatByDecimal());
    }

    public function includeCompanyCr(Company $company): Primitive
    {
        return $this->primitive($company->company_cr);
    }

    public function includeStatus(Company $company): Primitive
    {
        return $this->primitive([
            'value' => $company->status->value,
            'description' => $company->status->description,
        ]);
    }

    public function includeOrdersCount(Company $company): Primitive
    {
        return $this->primitive($company->orders_count);
    }

    public function includeDoesOrderRequireApproval(Company $company): Primitive
    {
        return $this->primitive($company->does_order_require_approval);
    }

    public function includeWebhookSecretKey(Company $company): Primitive
    {
        return $this->primitive($company->webhook_secret_key);
    }

    public function includeCreatedAt(Company $company): Primitive
    {
        return $this->primitive(optional($company->created_at)->format('Y-m-d'));
    }

    public function includePublicStatusComment(Company $company): Primitive
    {
        return $this->primitive($company->public_status_comment);
    }

    public function includeDriver(Company $company): Primitive
    {
        return $this->primitive($company->driver);
    }
}
