<?php

namespace Tests\Traits;

use App\Enums\Area;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\EnquiryReply;
use App\Models\User;

trait InteractsWithEnquiry
{
    public function createEnquiry(
        User $user = null,
        int $enquiryStatus = EnquiryStatus::UnderReview,
        array $data = []
    ): Enquiry {
        $userId = null;
        $roleId = null;

        if ($user) {
            $userId = $user->id;
            $roleId = $user->roles()
                ->whereIn('name', Area::roles(Area::Lender))
                ->firstOrFail()
                ->id;
        }

        return Enquiry::create(
            array_merge([
                'subject' => 'Test Enquiry',
                'body' => 'This is a test Enquiry body',
                'name' => 'Test User',
                'email' => 'userTest@example.com',
                'phone_number' => '+966547125919',
                'status' => $enquiryStatus,
                'user_id' => $userId,
                'role_id' => $roleId,
            ], $data)
        );
    }

    public function createEnquiryReply(
        Enquiry $enquiry,
        User $user = null,
        array $data = []
    ): EnquiryReply {
        $userId = null;
        $roleId = null;

        if ($user) {
            $userId = $user->id;
            $roleId = $user->roles()
                ->whereIn('name', Area::roles(Area::SuperAdmin))
                ->firstOrFail()
                ->id;
        }

        return EnquiryReply::create(
            array_merge([
                'body' => 'This is enquiry reply body test',
                'user_id' => $userId,
                'enquiry_id' => $enquiry->id,
                'role_id' => $roleId,
            ], $data)
        );
    }
}
