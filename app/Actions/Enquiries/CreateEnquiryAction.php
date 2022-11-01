<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\CreateEnquiry;
use App\Models\Enquiry;
use Modules\Grantify\Facades\Grantify;

class CreateEnquiryAction implements CreateEnquiry
{
    /**
     * Create new enquiry.
     *
     * @param  array  $data
     * @return Enquiry
     */
    public function handle(array $data): Enquiry
    {
        if (! empty($data['role'])) {
            $data['role_id'] = Grantify::findRole($data['role'])->id;
        }

        if (! empty($data['phone_number'])) {
            $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);
        }

        // create Enquiry
        return Enquiry::create($data);
    }
}
