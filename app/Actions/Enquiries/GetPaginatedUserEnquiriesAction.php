<?php

namespace App\Actions\Enquiries;

use App\Actions\Contracts\Enquiries\GetPaginatedUserEnquiries;
use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\User;
use App\Support\QueryScoper\Scopes\Enquiry\EnquiryStatusScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedUserEnquiriesAction implements GetPaginatedUserEnquiries
{
    /**
     * Create new enquiry.
     */
    public function handle(int $userId): LengthAwarePaginator
    {
        $user = User::find($userId);
        $enquiries = Enquiry::query();
        if ($user->hasRole(Role::LenderAdmin)) {
            $enquiries = $enquiries->whereHas('user', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        } else {
            $enquiries = $enquiries->where('user_id', $userId);
        }

        return $enquiries
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
