<?php

namespace App\Actions\Contracts\Enquiries;

use App\Models\EnquiryReply;

interface ReplyToEnquiry
{
    /**
     * Create new enquiry.
     *
     * @param  array  $data
     * @return EnquiryReply
     */
    public function handle(array $data): EnquiryReply;
}
