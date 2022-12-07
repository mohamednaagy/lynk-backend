<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\ListUserEnquiries;
use App\Models\Enquiry;
use App\Support\QueryScoper\Scopes\Enquiry\EnquiryStatusScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// __REVIEW__ change name to GetPaginatedUserEnquiriesAction to be consistent with the application
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
        // __REVIEW__ break down chain methods on new lines to be easier to read
        return Enquiry::where('user_id', $userId)->latest()->toScopes($this->scopes())->paginate();
    }

    private function scopes()
    {
        return [
            'status' => new EnquiryStatusScope(),
        ];
    }
}
