<?php

namespace App\Actions\Contracts\Enquiries;

use App\Models\Enquiry;

interface CreateEnquiry
{
    /**
     * Create new enquiry.
     */
    public function handle(array $data): Enquiry;
}
