<?php

namespace Tests\Feature\Endpoints\Api\V1\Visitor\Enquiries;

use App\Models\Enquiry;
use App\Transformers\EnquiryTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithEnquiry;

class GetVisitorEnquiryTest extends TestCase
{
    use RefreshDatabase, InteractsWithEnquiry;

    const BaseUrl = 'api/v1/visitor/enquiries';

    private static Enquiry $visitorEnquiry;

    private static string $enquirySignature;

    private static string $signedVisitorEnquiryUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$visitorEnquiry = $this->createEnquiry();
        self::$enquirySignature = explode(
            'signature=',
            URL::signedRoute('api.v1.visitor.enquiry', ['enquiry' => self::$visitorEnquiry->id])
        )[1];
        self::$signedVisitorEnquiryUrl = self::BaseUrl.'/'.self::$visitorEnquiry->id.'?signature='.self::$enquirySignature;
    }

    /**
     * @return void
     */
    public function test_that_visitor_can_get_enquiry_with_signature_url_succeed(): void
    {
        $this->getJson(self::$signedVisitorEnquiryUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$visitorEnquiry, new EnquiryTransformer())
                    ->parseIncludes([
                        'id',
                        'subject',
                        'status',
                        'creation_date',
                        'body',
                        'replies.id',
                        'replies.body',
                        'replies.creation_date',
                        'replies.creator',
                        'replySignature',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_visitor_cannot_get_enquiry_without_signature_url_succeed(): void
    {
        $this->getJson(self::BaseUrl.'/'.self::$visitorEnquiry->id)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('Invalid signature.'));
    }

    /**
     * @return void
     */
    public function test_that_visitor_cannot_get_not_found_enquiry_succeed(): void
    {
        $this->getJson(self::BaseUrl.'/111?signature='.self::$enquirySignature)
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'No query results for model [App\Models\Enquiry] 111');
    }
}
