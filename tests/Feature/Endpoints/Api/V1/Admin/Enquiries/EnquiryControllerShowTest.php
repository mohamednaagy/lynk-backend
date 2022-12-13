<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Enquiries;

use App\Actions\Contracts\Enquiries\GetPaginatedEnquiries;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\Enquiry;
use App\Models\User;
use App\Transformers\EnquiryTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithEnquiry;

class EnquiryControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithEnquiry;

    const BaseUrl = 'api/v1/admin/enquiries';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Enquiry $visitorEnquiry;

    private static $paginatedEnquiries;

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
            perm(Area::SuperAdmin, [Subject::Enquiries, Action::Show])
        );
        self::$visitorEnquiry = $this->createEnquiry();
        self::$paginatedEnquiries = $this->app->make(GetPaginatedEnquiries::class);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_enquiries(): void
    {
        $this->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_index_enquiries(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::BaseUrl.'/'.self::$visitorEnquiry->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$visitorEnquiry, new EnquiryTransformer())
                    ->parseIncludes([
                        'id',
                        'subject',
                        'status',
                        'creation_date',
                        'creator',
                        'body',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_index_lender_settings(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->getJson(self::BaseUrl.'/'.self::$visitorEnquiry->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$visitorEnquiry, new EnquiryTransformer())
                    ->parseIncludes([
                        'id',
                        'subject',
                        'status',
                        'creation_date',
                        'creator',
                        'body',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_cannot_index_lender_settings_with_no_permission(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::BaseUrl.'/'.self::$visitorEnquiry->id)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }
}
