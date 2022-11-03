<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\ListUserEnquiries;
use App\Models\Enquiry;
use App\Support\QueryScoper\Scopes\Enquiry\EnquiryStatusScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUserEnquiriesAction implements ListUserEnquiries
{
    /**
     * Create new enquiry.
     *
     * @param  int  $userId
     * @return
     */
    public function handle(int $userId): LengthAwarePaginator
    {
        return Enquiry::where('user_id', $userId)->latest()->toScopes($this->scopes())->paginate();
    }

    private function scopes()
    {
        return [
            'status' => new EnquiryStatusScope(),
        ];
    }
}
