<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\GetPaginatedUserEnquiries;
use App\Models\Enquiry;
use App\Support\QueryScoper\Scopes\Enquiry\EnquiryStatusScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedUserEnquiriesAction implements GetPaginatedUserEnquiries
{
    /**
     * Create new enquiry.
     *
     * @param  int  $userId
     * @return
     */
    public function handle(int $userId): LengthAwarePaginator
    {
        return Enquiry::where('user_id', $userId)
            ->latest()
            ->toScopes($this->scopes())
            ->paginate();
    }

    private function scopes()
    {
        return [
            'status' => new EnquiryStatusScope(),
        ];
    }
}
