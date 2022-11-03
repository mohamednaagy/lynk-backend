<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\CreateEnquiry;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Illuminate\Support\Arr;

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
        $data['status'] = EnquiryStatus::UnderReview;

        if (! empty($data['phone_number'])) {
            $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);
        }

        // create Enquiry
        return Enquiry::create(Arr::only(
            $data,
            [
                'subject',
                'body',
                'status',
                'name',
                'email',
                'phone_number',
                'user_id',
                'role_id',
            ]
        ));
    }
}
