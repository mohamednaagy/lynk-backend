<?php

namespace App\Actions\Contracts\Enquiries;

use App\Models\Enquiry;

interface CreateEnquiry
{
    /**
     * Create new enquiry.
     *
     * @param  array  $data
     * @return Enquiry
     */
    public function handle(array $data): Enquiry;
}
