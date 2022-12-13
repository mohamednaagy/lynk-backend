<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Enquiries;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\EnquiryStatus;
use App\Enums\Subject;
use App\Mail\ReplyToVisitorEnquiry;
use App\Models\Enquiry;
use App\Models\EnquiryReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithEnquiry;

class EnquiryReplyControllerStoreTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithEnquiry;

    const BaseUrl = 'api/v1/admin/enquiries/';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Enquiry $visitorEnquiry;

    private static EnquiryReply $adminEnquiryReply;

    private static string $enquiryReplyUrl;

    private static array $enquiryReplyData = [
        'body' => 'This is enquiry reply body test',
        'redirect_url' => 'http://localhost:8000/api/v1/visitor/enquiries/:enquiry',
    ];

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
            perm(Area::SuperAdmin, [Subject::EnquiryReplies, Action::Create])
        );
        self::$visitorEnquiry = $this->createEnquiry();
        self::$enquiryReplyUrl = self::BaseUrl.self::$visitorEnquiry->id.'/replies';
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_create_enquiry_reply(): void
    {
        $this->getJson(self::$enquiryReplyUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_create_enquiry_reply(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$enquiryReplyUrl, self::$enquiryReplyData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_create_enquiry_reply(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->postJson(self::$enquiryReplyUrl, self::$enquiryReplyData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_cannot_create_enquiry_reply_with_no_permission(): void
    {
        $this->actingAs(self::$manager)
            ->postJson(self::$enquiryReplyUrl, self::$enquiryReplyData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }

    /**
     * @return void
     */
    public function test_that_enquiry_reply_has_been_created_and_mail_sent_to_the_visitor(): void
    {
        Mail::fake();

        $this->actingAs(self::$admin)
            ->postJson(self::$enquiryReplyUrl, self::$enquiryReplyData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);

        Mail::assertSent(ReplyToVisitorEnquiry::class);

        $userEmail = self::$visitorEnquiry->email;
        Mail::assertSent(ReplyToVisitorEnquiry::class, function ($mail) use ($userEmail) {
            return $mail->hasTo($userEmail);
        });
    }

    /**
     * @return void
     */
    public function test_that_enquiry_reply_has_been_created_and_status_changed_to_resolved(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$enquiryReplyUrl, self::$enquiryReplyData)
            ->assertStatus(Response::HTTP_OK);

        $enquiry = $this->findEnquiry(self::$visitorEnquiry->id);
        $this->assertEquals(
            EnquiryStatus::Resolved,
            $enquiry->status->value
        );
    }

    /**
     * @return void
     */
    public function test_that_enquiry_reply_has_been_created_and_status_changed_to_specific_status(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$enquiryReplyUrl, array_merge(['status' => EnquiryStatus::Closed], self::$enquiryReplyData))
            ->assertStatus(Response::HTTP_OK);

        $enquiry = $this->findEnquiry(self::$visitorEnquiry->id);
        $this->assertEquals(
            EnquiryStatus::Closed,
            $enquiry->status->value
        );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_body_cannot_create_enquiry_reply(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$enquiryReplyUrl, Arr::except(self::$enquiryReplyData, ['body']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The body field is required.',
                'errors' => [
                    'body' => [
                        'The body field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_redirect_url_cannot_create_enquiry_reply(): void
    {
        $this->actingAs(self::$admin)
            ->postJson(self::$enquiryReplyUrl, Arr::except(self::$enquiryReplyData, ['redirect_url']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The redirect url field is required.',
                'errors' => [
                    'redirect_url' => [
                        'The redirect url field is required.',
                    ],
                ],
            ]);
    }
}
