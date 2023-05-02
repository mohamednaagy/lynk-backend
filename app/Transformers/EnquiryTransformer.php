<?php

namespace App\Transformers;

use App\Models\Enquiry;
use Illuminate\Support\Facades\URL;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class EnquiryTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'subject',
        'status',
        'creation_date',
        'body',
        'creator',
        'replies',
        'replySignature',
        'company',
    ];

    public function transform(Enquiry $enquiry): array
    {
        return [];
    }

    public function includeId(Enquiry $enquiry): Primitive
    {
        return $this->primitive($enquiry->id);
    }

    public function includeSubject(Enquiry $enquiry): Primitive
    {
        return $this->primitive($enquiry->subject);
    }

    public function includeStatus(Enquiry $enquiry): Primitive
    {
        return $this->primitive([
            'description' => $enquiry->status->description,
            'value' => $enquiry->status->value,
        ]);
    }

    public function includeCreationDate(Enquiry $enquiry): Primitive
    {
        return $this->primitive($enquiry->created_at->format('Y-m-d h:i A'));
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

    public function includeCompany(Enquiry $enquiry): Primitive
    {
        if (! $enquiry->user || ! $enquiry->user->company) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'id' => $enquiry->user->company->id,
            'name' => $enquiry->user->company->name,
        ]);
    }

    public function includeReplies(Enquiry $enquiry): Collection
    {
        return $this->collection($enquiry->replies, new EnquiryReplyTransformer);
    }

    public function includeReplySignature(Enquiry $enquiry): Primitive
    {
        return $this->primitive(explode(
            'signature=',
            URL::signedRoute('api.v1.visitor.enquiry.reply', ['enquiry' => $enquiry->id]))[1]
        );
    }
}
