<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\GetPaginatedEnquiries;
use App\Models\Enquiry;
use App\Support\QueryScoper\Scopes\Enquiry\EnquiryCreatorScope;
use App\Support\QueryScoper\Scopes\Enquiry\EnquiryStatusScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedEnquiriesAction implements GetPaginatedEnquiries
{
    /**
     * @param  int  $paginate
     * @return LengthAwarePaginator
     */
    public function handle(int $paginate = 9): LengthAwarePaginator
    {
        return Enquiry::query()
            ->toScopes($this->scopes())
            ->latest()
            ->paginate($paginate);
    }

    private function scopes(): array
    {
        return [
            'status' => new EnquiryStatusScope(),
            'creator' => new EnquiryCreatorScope(),
        ];
    }
}
