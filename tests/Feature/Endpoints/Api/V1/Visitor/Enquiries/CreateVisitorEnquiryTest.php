<?php

namespace Tests\Feature\Endpoints\Api\V1\Visitor\Enquiries;

use App\Enums\EnquiryStatus;
use App\Mail\AccessVisitorEnquiry;
use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithEnquiry;

class CreateVisitorEnquiryTest extends TestCase
{
    use RefreshDatabase, InteractsWithEnquiry;

    const BaseUrl = 'api/v1/visitor/enquiries/';

    private static Enquiry $visitorEnquiry;

    private static array $enquiryData = [
        'subject' => 'This is test subject',
        'body' => 'This is enquiry reply body test',
        'name' => 'Test Name',
        'email' => 'visitor@example.com',
        'phone_country_code' => 'SA',
        'phone_number' => '547125919',
        'redirect_url' => 'http://localhost:8000/api/v1/visitor/enquiries/:enquiry',
    ];

    /**
     * @return void
     */
    public function test_that_visitor_can_create_enquiry_succeed(): void
    {
        $this->postJson(self::BaseUrl, self::$enquiryData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'status',
                    'creation_date',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_visitor_can_create_enquiry_with_sending_email_to_creator_succeed(): void
    {
        Mail::fake();

        $this->postJson(self::BaseUrl, self::$enquiryData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'status',
                    'creation_date',
                ],
            ]);

        $userEmail = self::$enquiryData['email'];
        Mail::assertSent(AccessVisitorEnquiry::class, function ($mail) use ($userEmail) {
            return $mail->hasTo($userEmail);
        });
    }

    /**
     * @return void
     */
    public function test_that_visitor_can_create_enquiry_and_status_set_as_under_review_succeed(): void
    {
        $response = $this->postJson(self::BaseUrl, self::$enquiryData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'status',
                    'creation_date',
                ],
            ]);

        $this->assertEquals(
            EnquiryStatus::UnderReview,
            $response->getOriginalContent()->data->status->value
        );
    }

    /**
     * @return void
     */
    public function test_that_visitor_without_subject_cannot_create_enquiry_failed(): void
    {
        $this->postJson(self::BaseUrl, Arr::except(self::$enquiryData, ['subject']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The subject field is required.',
                'errors' => [
                    'subject' => [
                        'The subject field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_visitor_without_body_cannot_create_enquiry_failed(): void
    {
        $this->postJson(self::BaseUrl, Arr::except(self::$enquiryData, ['body']))
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
    public function test_that_visitor_without_name_cannot_create_enquiry_failed(): void
    {
        $this->postJson(self::BaseUrl, Arr::except(self::$enquiryData, ['name']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The name field is required.',
                'errors' => [
                    'name' => [
                        'The name field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_visitor_without_email_cannot_create_enquiry_failed(): void
    {
        $this->postJson(self::BaseUrl, Arr::except(self::$enquiryData, ['email']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The email field is required.',
                'errors' => [
                    'email' => [
                        'The email field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_visitor_without_phone_number_cannot_create_enquiry_failed(): void
    {
        $this->postJson(self::BaseUrl, Arr::except(self::$enquiryData, ['phone_number']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone number field is required.',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_visitor_without_phone_country_code_cannot_create_enquiry_failed(): void
    {
        $this->postJson(self::BaseUrl, Arr::except(self::$enquiryData, ['phone_country_code']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
                'errors' => [
                    'phone_country_code' => [
                        'The phone country code field is required when phone number is present.',
                    ],
                    'phone_number' => [
                        'The phone number is not a valid phone number.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_visitor_without_redirect_url_cannot_create_enquiry_failed(): void
    {
        $this->postJson(self::BaseUrl, Arr::except(self::$enquiryData, ['redirect_url']))
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

    /**
     * @return void
     */
    public function test_that_visitor_with_invalid_and_not_whitelisted_redirect_url_cannot_create_enquiry_failed(): void
    {
        $this->postJson(
            self::BaseUrl,
            array_merge(
                self::$enquiryData,
                [
                    'redirect_url' => 'http://invalid-domain/api/v1/visitor/enquiries/:enquiry',
                ]
            )
        )
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The redirect url is not whitelisted.',
                'errors' => [
                    'redirect_url' => [
                        'The redirect url is not whitelisted.',
                    ],
                ],
            ]);
    }
}
