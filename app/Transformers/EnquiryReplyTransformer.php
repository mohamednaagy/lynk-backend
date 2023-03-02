<?php

namespace App\Transformers;

use App\Models\EnquiryReply;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class EnquiryReplyTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'body',
        'creation_date',
        'creator',
    ];

    public function transform(EnquiryReply $enquiryReply): array
    {
        return [];
    }

    public function includeId(EnquiryReply $enquiryReply): Primitive
    {
        return $this->primitive($enquiryReply->id);
    }

    public function includeBody(EnquiryReply $enquiryReply): Primitive
    {
        return $this->primitive($enquiryReply->body);
    }

    public function includeCreationDate(EnquiryReply $enquiryReply): Primitive
    {
        return $this->primitive($enquiryReply->created_at->format('Y-m-d h:m A'));
    }

    public function includeCreator(EnquiryReply $enquiryReply): Primitive
    {
        if ($enquiryReply->user) {
            return $this->primitive([
                'id' => $enquiryReply->user->id,
                'name' => $enquiryReply->user->full_name,
            ]);
        }

        if (is_null($enquiryReply->enquiry)) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'email' => $enquiryReply->enquiry->email,
            'name' => $enquiryReply->enquiry->name,
            'phone_number' => $enquiryReply->enquiry->phone_number,
        ]);
    }
}
