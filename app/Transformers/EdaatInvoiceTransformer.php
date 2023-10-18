<?php

namespace App\Transformers;

use App\Models\EdaatInvoice;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class EdaatInvoiceTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'invoice_number',
        'amount',
        'amount_formatted',
        'creator',
        'company_name',
        'company_number',
        'status',
        'created_at',
        'company',
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
        return $this->primitive($edaatInvoice->amount->convertAndFormatByDecimal());
    }

    public function includeAmountFormatted(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive(number_format($edaatInvoice->amount->convertAndFormatByDecimal(), 2));
    }

    public function includeCompanyName(): Primitive
    {
        return $this->primitive(trans('common.edaat'));
    }

    public function includeCompanyNumber(): Primitive
    {
        // 903 is a static number for Edaat company
        return $this->primitive(903);
    }

    public function includeCreator(EdaatInvoice $edaatInvoice): Primitive
    {
        if (is_null($edaatInvoice->creator)) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'id' => $edaatInvoice->creator->id,
            'name' => $edaatInvoice->creator->full_name,
        ]);
    }

    public function includeCompany(EdaatInvoice $edaatInvoice): Primitive
    {
        if (is_null($edaatInvoice->company)) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'id' => $edaatInvoice->company->id,
            'name' => $edaatInvoice->company->name,
        ]);
    }

    public function includeCreatedAt(EdaatInvoice $edaatInvoice): Primitive
    {
        return $this->primitive($edaatInvoice->created_at->format('Y-m-d h:i A'));
    }
}
