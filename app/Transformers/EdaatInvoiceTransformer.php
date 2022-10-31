<?php

namespace App\Transformers;

use App\Models\EdaatInvoice;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class EdaatInvoiceTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'id',
        'invoice_number',
        'amount',
        'creator',
        'company_name',
        'company_number',
        'status',
        'created_at',
    ];

    protected array $availableIncludes = [
        'company',
        'creator',
    ];

    public function transform(EdaatInvoice $edaatInvoice): array
    {
        return [];
    }

    public function includeId(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive($edaatInvoice->id);
    }

    public function includeInvoiceNumber(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive($edaatInvoice->invoice_number);
    }

    public function includeStatus(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive([
            'value' => $edaatInvoice->status->value,
            'description' => $edaatInvoice->status->description,
        ]);
    }

    public function includeAmount(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive($edaatInvoice->amount);
    }

    public function includeCompanyName(): Primitive
    {
        return $this->primitive(trans('common.edaat'));
    }

    public function includeCompanyNumber(): Primitive
    {
        return $this->primitive(903);
    }

    public function includeCreator(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive([
            'id' => $edaatInvoice->creator->id,
            'name' => $edaatInvoice->creator->full_name,
        ]);
    }

    public function includeCompany(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive([
            'id' => $edaatInvoice->company->id,
            'name' => $edaatInvoice->company->name,
        ]);
    }

    public function includeCreatedAt(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive($edaatInvoice->created_at->format('Y-m-d h:m A'));
    }
}
