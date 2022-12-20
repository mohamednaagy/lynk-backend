<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Enquiries;

use App\Actions\Contracts\Enquiries\GetPaginatedEnquiries;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\Enquiry;
use App\Models\EnquiryReply;
use App\Models\User;
use App\Transformers\EnquiryReplyTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithEnquiry;

class EnquiryReplyControllerIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithEnquiry;

    const BaseUrl = 'api/v1/admin/enquiries/';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Enquiry $visitorEnquiry;

    private static EnquiryReply $adminEnquiryReply;

    private static $paginatedEnquiries;

    private static string $enquiryReplyUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager();
        self::$managerHasPermission = $this->createManager(
            'managerHasPermission@bim.com',
            perm(Area::SuperAdmin, [Subject::EnquiryReplies, Action::Show])
        );
        self::$visitorEnquiry = $this->createEnquiry();
        self::$adminEnquiryReply = $this->createEnquiryReply(self::$visitorEnquiry, self::$admin);
        self::$paginatedEnquiries = $this->app->make(GetPaginatedEnquiries::class);
        self::$enquiryReplyUrl = self::BaseUrl.self::$visitorEnquiry->id.'/replies';
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_enquiry_replies(): void
    {
        $this->getJson(self::$enquiryReplyUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_index_enquiry_replies(): void
    {
        self::$visitorEnquiry->load('replies');

        $this->actingAs(self::$admin)
            ->getJson(self::$enquiryReplyUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$visitorEnquiry->replies, new EnquiryReplyTransformer())
                    ->parseIncludes([
                        'id',
                        'body',
                        'creation_date',
                        'creator',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_index_enquiry_replies(): void
    {
        self::$visitorEnquiry->load('replies');

        $this->actingAs(self::$managerHasPermission)
            ->getJson(self::$enquiryReplyUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$visitorEnquiry->replies, new EnquiryReplyTransformer())
                    ->parseIncludes([
                        'id',
                        'body',
                        'creation_date',
                        'creator',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_cannot_index_enquiry_replies_with_no_permission(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::$enquiryReplyUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }
}
