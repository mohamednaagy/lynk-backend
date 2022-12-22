<?php

namespace Tests\Feature\Endpoints\Api\V1\Visitor\Enquiries;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithEnquiry;

class CreateVisitorEnquiryReplyTest extends TestCase
{
    use RefreshDatabase, InteractsWithEnquiry;

    const BaseUrl = 'api/v1/visitor/enquiries/';

    private static Enquiry $visitorEnquiry;

    private static string $enquiryReplySignature;

    private static string $signedVisitorEnquiryReplyUrl;

    private static array $enquiryReplyData = [
        'body' => 'This is enquiry reply body test',
    ];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$visitorEnquiry = $this->createEnquiry();
        self::$enquiryReplySignature = explode(
            'signature=',
            URL::signedRoute('api.v1.visitor.enquiry.reply', ['enquiry' => self::$visitorEnquiry->id])
        )[1];
        self::$signedVisitorEnquiryReplyUrl = self::BaseUrl.
            self::$visitorEnquiry->id.
            '/reply'.
            '?signature='.
            self::$enquiryReplySignature;
    }

    /**
     * @return void
     */
    public function test_that_visitor_cannot_create_enquiry_reply_without_signature_url_failed(): void
    {
        $this->postJson(self::BaseUrl.self::$visitorEnquiry->id.'/reply')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('Invalid signature.'));
    }

    /**
     * @return void
     */
    public function test_that_visitor_can_create_enquiry_reply_succeed(): void
    {
        $this->postJson(self::$signedVisitorEnquiryReplyUrl, self::$enquiryReplyData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'body',
                    'creation_date',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_enquiry_reply_has_been_created_and_status_changed_to_be_under_review(): void
    {
        $this->postJson(self::$signedVisitorEnquiryReplyUrl, self::$enquiryReplyData)
            ->assertStatus(Response::HTTP_OK);

        $this->assertEquals(
            EnquiryStatus::UnderReview,
            self::$visitorEnquiry->refresh()->status->value
        );
    }

    /**
     * @return void
     */
    public function test_that_visitor_without_body_cannot_create_enquiry_reply_failed(): void
    {
        $this->postJson(self::$signedVisitorEnquiryReplyUrl, Arr::except(self::$enquiryReplyData, ['body']))
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
}
