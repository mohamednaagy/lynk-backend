<?php

namespace App\Transformers;

use App\Models\Enquiry;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class EnquiryTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'body',
        'creator',
        'replies',
    ];

    public function transform(Enquiry $enquiry): array
    {
        return [
            'id' => $enquiry->id,
            'subject' => $enquiry->subject,
            'status' => [
                'description' => $enquiry->status->description,
                'value' => $enquiry->status->value,
            ],
            'creation_date' => $enquiry->created_at->format('Y-m-d h:m A'),
        ];
    }

    public function includeBody(Enquiry $enquiry): Primitive
    {
        return $this->primitive($enquiry->body);
    }

    public function includeCreator(Enquiry $enquiry): Primitive
    {
        if ($enquiry->user) {
            return $this->primitive([
                'id' => $enquiry->user->id,
                'name' => $enquiry->user->full_name,
            ]);
        }

        return $this->primitive([
            'email' => $enquiry->email,
            'name' => $enquiry->name,
            'phone_number' => $enquiry->phone_number,
        ]);
    }
}
