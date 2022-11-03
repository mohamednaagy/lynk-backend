<?php

namespace App\Actions\Contracts\Enquiries;

use App\Models\EnquiryReplies;

interface ReplyToEnquiry
{
    /**
     * Create new enquiry.
     *
     * @param  array  $data
     * @return EnquiryReplies
     */
    public function handle(array $data): EnquiryReplies;
}
