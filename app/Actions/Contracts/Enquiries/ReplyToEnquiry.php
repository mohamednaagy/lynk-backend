<?php

namespace App\Actions\Contracts\Enquiries;

use App\Models\EnquiryReply;

interface ReplyToEnquiry
{
    /**
     * Create new enquiry.
     */
    public function handle(array $data): EnquiryReply;
}
