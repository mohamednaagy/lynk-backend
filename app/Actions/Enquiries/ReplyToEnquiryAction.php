<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\ReplyToEnquiry;
use App\Models\EnquiryReplies;
use Illuminate\Support\Arr;

class ReplyToEnquiryAction implements ReplyToEnquiry
{
    /**
     * Create new enquiry.
     *
     * @param  array  $data
     * @return EnquiryReplies
     */
    public function handle(array $data): EnquiryReplies
    {
        return EnquiryReplies::create(Arr::only(
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
