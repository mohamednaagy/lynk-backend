<?php

namespace App\Transformers;

use App\Models\EnquiryReplies;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class EnquiryReplyTransformer extends TransformerAbstract
{
    // __REVIEW__ move all to available includes
    protected array $defaultIncludes = [
        'creator',
    ];

    protected array $availableIncludes = [];

    // __REVIEW__ move all to available includes
    // __REVIEW__ here argument name should singular not plural !!! (this applies to all includeXXXX)
    public function transform(EnquiryReplies $enquiryReplies): array
    {
        return [
            'id' => $enquiryReplies->id,
            'body' => $enquiryReplies->body,
            'creation_date' => $enquiryReplies->created_at->format('Y-m-d h:m A'),
        ];
    }

    public function includeBody(EnquiryReplies $enquiryReplies): Primitive
    {
        return $this->primitive($enquiryReplies->body);
    }

    public function includeCreator(EnquiryReplies $enquiryReplies): Primitive
    {
        if ($enquiryReplies->user) {
            return $this->primitive([
                'id' => $enquiryReplies->user->id,
                'name' => $enquiryReplies->user->full_name,
            ]);
        }

        return $this->primitive([
            'email' => $enquiryReplies->enquiry->email,
            'name' => $enquiryReplies->enquiry->name,
            'phone_number' => $enquiryReplies->enquiry->phone_number,
        ]);
    }
}
