<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\ReplyToEnquiry;
use App\Models\EnquiryReply;
use Illuminate\Support\Arr;

class ReplyToEnquiryAction implements ReplyToEnquiry
{
    /**
     * Create new enquiry.
     *
     * @param  array  $data
     * @return EnquiryReply
     */
    public function handle(array $data): EnquiryReply
    {
        return EnquiryReply::create(Arr::only(
            $data,
            [
                'body',
                'user_id',
                'enquiry_id',
                'role_id',
            ]
        ));
    }
}
